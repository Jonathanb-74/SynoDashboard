<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

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
        Schema::defaultStringLength(191);
        $this->applySmtpSettings();
    }

    private function applySmtpSettings(): void
    {
        try {
            if (!Schema::hasTable('app_settings')) {
                return;
            }
            foreach (AppSetting::getGroup('mail.') as $key => $value) {
                config([$key => $value ?: null]);
            }
        } catch (\Throwable) {
            // DB not ready (first install, artisan commands, etc.)
        }
    }
}
