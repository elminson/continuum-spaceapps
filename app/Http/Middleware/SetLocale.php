<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public const LOCALES = ['en', 'es'];

    public function handle(Request $request, Closure $next): Response
    {
        // The chosen language first; on a first visit, the browser's preferred language.
        $locale = $request->cookie('locale') ?: $request->getPreferredLanguage(self::LOCALES);
        app()->setLocale(in_array($locale, self::LOCALES, true) ? $locale : 'en');

        return $next($request);
    }
}
