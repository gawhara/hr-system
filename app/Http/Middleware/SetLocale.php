<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    /**
     * Arabic boots by default for every session; English applies only when a
     * user explicitly opted in (users.locale) or a guest toggled the session.
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->user()?->locale
            ?? $request->session()->get('locale')
            ?? config('app.locale', 'ar');

        app()->setLocale(in_array($locale, ['ar', 'en'], true) ? $locale : 'ar');

        return $next($request);
    }
}
