<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Reads and writes the tunable inventory thresholds (FR-5.2).
 *
 * The thresholds live in config/inventory.php with environment-variable
 * defaults, but the department can tune them at runtime without a code change
 * through the Settings screen. An override is stored as a single row in the
 * settings table; a missing row means "use the configured default".
 *
 * All consumers read the values through config('inventory.*') at call time
 * (Batch::expiryStatus(), BidService::needsReorder(), DashboardService), so
 * merging the stored overrides into config at boot makes every call site honour
 * them without any further change. The merge is applied by AppServiceProvider,
 * which also captures the pristine config-file defaults first so this service
 * can always report what the default for a setting is.
 *
 * @phpstan-type SettingDefinition array{
 *     label: string,
 *     type: 'int'|'float',
 *     config_key: string,
 *     min: int|float,
 *     unit: string,
 *     help: string,
 * }
 */
class SettingsService
{
    /**
     * The settings the department can tune, in display order.
     *
     * This is the single source of truth for a tunable setting: adding a new
     * one is a matter of adding an entry here (plus the corresponding default
     * in config/inventory.php). The page, the validation rules, and the config
     * merge are all derived from it.
     *
     * @var array<string, SettingDefinition>
     */
    protected const array DEFINITIONS = [
        'near_expiry_days' => [
            'label' => 'Near-expiry window',
            'type' => 'int',
            'config_key' => 'inventory.near_expiry_days',
            'min' => 0,
            'unit' => 'days',
            'help' => 'A batch is flagged as near expiry this many days before its expiration date (FR-3.2).',
        ],
        'low_stock_threshold' => [
            'label' => 'Low-stock threshold',
            'type' => 'float',
            'config_key' => 'inventory.low_stock_threshold',
            'min' => 0,
            'unit' => 'units',
            'help' => 'An active supplier item at or below this running quantity is surfaced as low stock on the dashboard (FR-5.2).',
        ],
        'bid_reorder_threshold' => [
            'label' => 'Bid reorder threshold',
            'type' => 'float',
            'config_key' => 'inventory.bid_reorder_threshold',
            'min' => 0,
            'unit' => 'units',
            'help' => 'A bid with this much or less remaining is flagged as needing a new bid.',
        ],
    ];

    /**
     * Whether the settings table exists, memoised for the request.
     */
    protected ?bool $hasTable = null;

    /**
     * The tunable settings, keyed by name.
     *
     * @return array<string, SettingDefinition>
     */
    public function definitions(): array
    {
        return static::DEFINITIONS;
    }

    /**
     * Whether a setting with the given name is tunable.
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, static::DEFINITIONS);
    }

    /**
     * The stored overrides, keyed by setting name.
     *
     * Only settings with an explicit override appear here. The table is tiny,
     * so it is read directly rather than cached: an override written at boot
     * and by the Settings screen must always be reflected immediately.
     *
     * @return array<string, int|float>
     */
    public function overrides(): array
    {
        if (! $this->hasTable()) {
            return [];
        }

        /** @var array<string, mixed> $stored */
        $stored = Setting::query()->pluck('value', 'key')->all();

        $overrides = [];

        foreach (static::DEFINITIONS as $key => $definition) {
            $raw = $stored[$key] ?? null;

            if ($raw !== null && $raw !== '') {
                $overrides[$key] = $this->cast($key, $raw);
            }
        }

        return $overrides;
    }

    /**
     * The configured defaults for every tunable setting.
     *
     * The pristine config-file values are captured by AppServiceProvider before
     * the overrides are merged into config, so this always reports the default
     * the department would fall back to.
     *
     * @return array<string, int|float>
     */
    public function defaults(): array
    {
        $captured = config('inventory.defaults');

        if (is_array($captured)) {
            /** @var array<string, int|float> $captured */
            return $captured;
        }

        $defaults = [];

        foreach (static::DEFINITIONS as $key => $definition) {
            $defaults[$key] = $this->cast($key, config($definition['config_key']));
        }

        return $defaults;
    }

    /**
     * The values in effect, after applying any overrides to the defaults.
     *
     * @return array<string, int|float>
     */
    public function effective(): array
    {
        return array_merge($this->defaults(), $this->overrides());
    }

    /**
     * The rows to render on the Settings screen.
     *
     * @return list<array{
     *     key: string,
     *     label: string,
     *     type: 'int'|'float',
     *     min: int|float,
     *     unit: string,
     *     help: string,
     *     default: int|float,
     *     value: int|float,
     *     is_overridden: bool,
     * }>
     */
    public function rows(): array
    {
        $defaults = $this->defaults();
        $overrides = $this->overrides();

        $rows = [];

        foreach (static::DEFINITIONS as $key => $definition) {
            $rows[] = [
                'key' => $key,
                'label' => $definition['label'],
                'type' => $definition['type'],
                'min' => $definition['min'],
                'unit' => $definition['unit'],
                'help' => $definition['help'],
                'default' => $defaults[$key],
                'value' => $overrides[$key] ?? $defaults[$key],
                'is_overridden' => array_key_exists($key, $overrides),
            ];
        }

        return $rows;
    }

    /**
     * Store an override for a setting and apply it to config immediately.
     */
    public function set(string $key, mixed $value): Setting
    {
        $cast = $this->cast($key, $value);

        $setting = Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => (string) $cast],
        );

        $this->apply($key, $cast);

        return $setting;
    }

    /**
     * Clear an override so the configured default applies again.
     */
    public function reset(string $key): void
    {
        if (! $this->has($key)) {
            return;
        }

        Setting::query()->where('key', $key)->delete();

        $this->apply($key, $this->defaults()[$key]);
    }

    /**
     * Clear every override so the configured defaults apply again.
     */
    public function resetAll(): void
    {
        Setting::query()->delete();

        foreach ($this->defaults() as $key => $value) {
            $this->apply($key, $value);
        }
    }

    /**
     * Merge the stored overrides into the inventory config.
     *
     * Called once at boot by AppServiceProvider, after the pristine defaults
     * have been captured.
     */
    public function applyOverridesToConfig(): void
    {
        foreach ($this->overrides() as $key => $value) {
            $this->apply($key, $value);
        }
    }

    /**
     * Whether the settings table exists.
     *
     * Guards the boot-time merge so a fresh checkout (or a run before the
     * migration) degrades to the configured defaults instead of failing.
     */
    public function hasTable(): bool
    {
        if ($this->hasTable !== null) {
            return $this->hasTable;
        }

        try {
            return $this->hasTable = Schema::hasTable('settings');
        } catch (Throwable) {
            return $this->hasTable = false;
        }
    }

    /**
     * Write one value into the inventory config.
     */
    protected function apply(string $key, int|float $value): void
    {
        config([static::DEFINITIONS[$key]['config_key'] => $value]);
    }

    /**
     * Coerce a raw value to the setting's declared type.
     */
    protected function cast(string $key, mixed $value): int|float
    {
        return static::DEFINITIONS[$key]['type'] === 'int'
            ? (int) $value
            : (float) $value;
    }
}
