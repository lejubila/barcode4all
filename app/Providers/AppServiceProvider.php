<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
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
        // Behind an HTTPS reverse proxy (e.g. Apache ProxyPass terminating TLS):
        // when APP_URL is https, force generated URLs/assets to use https so the
        // page never mixes http resources, even if the proxy forwarded the
        // request over plain http. Trusted proxy headers are configured in
        // bootstrap/app.php.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
