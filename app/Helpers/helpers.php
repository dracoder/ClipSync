<?php

use Illuminate\Support\Facades\Storage;
use App\Models\GlobalSetting;

if (!function_exists('log_error')) {
    function log_error($exception, $dump = true)
    {
        if (config('app.env') == 'local' && $dump) {
            dd($exception);
        }
        \Illuminate\Support\Facades\Log::error($exception);
    }
}