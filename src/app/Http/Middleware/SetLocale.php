<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

final class SetLocale
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $available = config('app.available_locales', ['ru', 'en', 'kk']);

        $locale = $request->session()->get('locale');
        if ($locale === null && $request->user() !== null) {
            $userLocale = $request->user()->locale;
            if (is_string($userLocale) && in_array($userLocale, $available, true)) {
                $locale = $userLocale;
            }
        }

        if (is_string($locale) && in_array($locale, $available, true)) {
            App::setLocale($locale);
        } else {
            App::setLocale((string) config('app.locale'));
        }

        return $next($request);
    }
}
