<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Determines the UI language for each request.
 *
 * Priority:
 *   1. An explicit choice stored in the session (set via /locale/{locale}).
 *   2. Otherwise the language preferred by the browser (Accept-Language).
 *   3. Otherwise the application fallback locale.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'it'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale');

        if (! in_array($locale, self::SUPPORTED, true)) {
            // getPreferredLanguage() always returns one of the supplied values.
            $locale = $request->getPreferredLanguage(self::SUPPORTED);
        }

        app()->setLocale(
            in_array($locale, self::SUPPORTED, true) ? $locale : config('app.fallback_locale', 'en')
        );

        return $next($request);
    }
}
