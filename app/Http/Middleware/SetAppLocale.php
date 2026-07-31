<?php

// app/Http/Middleware/SetAppLocale.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetAppLocale
{
    public function handle(Request $request, Closure $next)
    {
        /*$locale = $request->header('Accept-Language', config('app.locale'));

        if (in_array($locale, ['ar', 'en'])) {
            App::setLocale($locale);
        }

        return $next($request);*/
        $locale = $request->header('Accept-Language', 'en');

        App::setLocale(
            in_array($locale, ['ar', 'en']) ? $locale : 'en'
        );

        return $next($request);
    }
}
