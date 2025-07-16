<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Opcodes\LogViewer\Facades\LogViewer;

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
        $this->allowLogViewer();
    }

    protected function allowLogViewer()
    {
        LogViewer::auth(function ($request) {
            if (
                $request->query('code') == config('log-viewer.access_code', 'clipsync')
            || (!empty($request->header('x-auth-code') && $request->header('x-auth-code') == config('log-viewer.access_code', 'clipsync')))
            ) {
                return true;
            }
            return abort(404);
        });
    }
}
