<?php

namespace OtpAuth;

use Illuminate\Support\ServiceProvider;

class SmsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/sms.php', 'sms');

        $this->app->singleton(SmsService::class, function ($app) {
            return new SmsService();
        });
    }

    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/sms.php' => config_path('sms.php'),
        ], 'sms-config');

        if (! class_exists('CreateOtpsTable')) {
            $this->publishes([
                __DIR__ . '/database/migrations/2025_01_01_000000_create_otps_table.php' => database_path('migrations/' . date('Y_m_d_His', time()) . '_create_otps_table.php'),
            ], 'sms-migrations');
        }
    }
}
