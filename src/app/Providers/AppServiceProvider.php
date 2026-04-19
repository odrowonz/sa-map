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
        $appUrl = config('app.url');
        if (! is_string($appUrl) || $appUrl === '') {
            return;
        }

        // Только scheme + host (+ port). Иначе при APP_URL с путём (/sa-map) и префиксе маршрутов
        // route() даёт …/sa-map/sa-map вместо …/sa-map/
        $parts = parse_url($appUrl);
        if (! empty($parts['host'])) {
            $root = ($parts['scheme'] ?? 'http').'://'.$parts['host'];
            if (! empty($parts['port'])) {
                $root .= ':'.$parts['port'];
            }
            URL::forceRootUrl($root);
        } else {
            URL::forceRootUrl($appUrl);
        }
    }
}
