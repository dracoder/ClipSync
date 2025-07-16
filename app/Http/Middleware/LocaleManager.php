<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleManager
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->hasCookie('locale')) {
            app()->setLocale($request->cookie('locale'));
        } else {
            $locale = substr($request->server('HTTP_ACCEPT_LANGUAGE'), 0, 2);
            app()->setLocale($locale);
        }
        $localeFiles = array_map('basename', glob(resource_path('lang/*.json')));
        $localeFiles = array_map(function($file){
            return str_replace('.json', '', $file);
        }, $localeFiles);
        if(!in_array(app()->getLocale(), $localeFiles)){
            app()->setLocale('en');
        }
        return $next($request);
    }
}
