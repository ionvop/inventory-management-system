<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\AuditLogger;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Lets an administrator tune the inventory thresholds at runtime (FR-5.2).
 *
 * The near-expiry window, low-stock threshold and bid reorder threshold ship
 * with defaults in config/inventory.php but are exposed here so the department
 * can adjust them without a code change. Each value is stored as an override;
 * clearing an override falls back to the configured default.
 *
 * Changing a threshold is a high-impact action, so it is restricted to
 * administrators and recorded on the audit trail (NFR-2.2, FR-8.1).
 */
class SettingController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected SettingsService $settings,
    ) {}

    /**
     * Display the tunable inventory settings.
     */
    public function index(): Response
    {
        return Inertia::render('Settings', [
            'settings' => $this->settings->rows(),
        ]);
    }

    /**
     * Save the submitted threshold overrides.
     *
     * Only values that differ from the current override are written, so saving
     * an unchanged form leaves no audit noise. A blank field is left unchanged;
     * clearing an override is a separate, explicit reset action.
     */
    public function store(): RedirectResponse
    {
        $rules = [];

        foreach ($this->settings->definitions() as $key => $definition) {
            $rules[$key] = ['nullable', 'numeric', 'min:'.$definition['min']];
        }

        $data = Validator::validate(Request::all(), $rules);

        $overrides = $this->settings->overrides();
        $defaults = $this->settings->defaults();

        $changed = 0;

        foreach ($this->settings->definitions() as $key => $definition) {
            $submitted = $data[$key] ?? null;

            if ($submitted === null || $submitted === '') {
                continue;
            }

            $value = $definition['type'] === 'int'
                ? (int) $submitted
                : (float) $submitted;

            if (array_key_exists($key, $overrides)) {
                $current = $overrides[$key];
            } else {
                $current = $defaults[$key];
            }

            if ($current === $value) {
                continue;
            }

            $before = $this->snapshot($key, $current);
            $subject = $this->settings->set($key, $value);

            $this->audit->record($subject, 'update', $before, $this->snapshot($key, $value));

            $changed++;
        }

        if ($changed === 0) {
            return Redirect::back()->with('success', 'No settings changed.');
        }

        return Redirect::back()->with('success', 'Settings saved.');
    }

    /**
     * Clear a single override, or every override, back to the defaults.
     *
     * Only overrides that actually exist are cleared and audited, so a reset is
     * never recorded when the value is already the default.
     */
    public function reset(): RedirectResponse
    {
        $data = Validator::validate(Request::all(), [
            'key' => ['nullable', 'string'],
        ]);

        $key = $data['key'] ?? null;

        if (is_string($key) && $key !== '') {
            if (! $this->settings->has($key)) {
                return Redirect::back()->withErrors([
                    'setting' => 'Unknown setting.',
                ]);
            }

            $setting = Setting::query()->where('key', $key)->first();

            if (! $setting instanceof Setting) {
                return Redirect::back()->with('success', 'That setting already uses its default.');
            }

            $before = $this->snapshot($key, $this->settings->effective()[$key]);

            $this->settings->reset($key);

            $this->audit->record(
                $setting,
                'reset',
                $before,
                $this->snapshot($key, $this->settings->defaults()[$key]),
            );

            return Redirect::back()->with('success', 'Setting reset to its default.');
        }

        $overrides = Setting::query()->get();

        if ($overrides->isEmpty()) {
            return Redirect::back()->with('success', 'Every setting already uses its default.');
        }

        $before = $this->settings->effective();
        $defaults = $this->settings->defaults();

        $this->settings->resetAll();

        foreach ($overrides as $setting) {
            $key = $setting->key;

            if (! $this->settings->has($key)) {
                continue;
            }

            $this->audit->record(
                $setting,
                'reset',
                $this->snapshot($key, $before[$key]),
                $this->snapshot($key, $defaults[$key]),
            );
        }

        return Redirect::back()->with('success', 'All settings reset to their defaults.');
    }

    /**
     * The auditable representation of a threshold.
     *
     * The label is included under "name" so the audit trail can show which
     * threshold was changed rather than a bare key.
     *
     * @return array<string, mixed>
     */
    protected function snapshot(string $key, int|float $value): array
    {
        return [
            'key' => $key,
            'name' => $this->settings->definitions()[$key]['label'],
            'value' => $value,
        ];
    }
}
