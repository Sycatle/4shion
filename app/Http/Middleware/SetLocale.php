<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

/** Applies the language the visitor picked (kept in the session) among the supported ones. */
final class SetLocale
{
    public const string SESSION_KEY = 'locale';

    /** @param  Closure(Request): Response  $next */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get(self::SESSION_KEY);

        if (is_string($locale) && array_key_exists($locale, Config::array('app.supported_locales'))) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
