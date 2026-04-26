<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        RateLimiter::for('profile-update', function (Request $request) {
            $key = (string) ($request->user()?->getAuthIdentifier() ?? $request->ip());

            return Limit::perMinute(15)->by('profile|'.$key);
        });

        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(10)->by('login|'.$request->ip());
        });

        RateLimiter::for('register', function (Request $request) {
            return Limit::perMinute(5)->by('register|'.$request->ip());
        });

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
