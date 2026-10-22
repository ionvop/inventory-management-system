<?php

namespace App\Providers;

use App\Services\SettingsService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->applyInventorySettings();
    }

    /**
     * Apply any stored threshold overrides on top of the config defaults.
     *
     * The tunable inventory thresholds (near-expiry window, low-stock and bid
     * reorder thresholds) ship with defaults in config/inventory.php but can be
     * tuned at runtime. The pristine defaults are captured first, so the
     * Settings screen can always show what the fallback would be, and then any
     * stored overrides are merged in. Every consumer reads the values through
     * config('inventory.*') at call time, so this one merge makes them all
     * honour the overrides without further change.
     */
    protected function applyInventorySettings(): void
    {
        $settings = $this->app->make(SettingsService::class);

        config()->set('inventory.defaults', $settings->defaults());

        $settings->applyOverridesToConfig();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
