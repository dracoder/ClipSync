<?php

use App\Http\Controllers\ClipController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Route::get('/', function () {
//     return view('welcome');
// });
Route::get('/', [PageController::class, 'welcome'])->name('welcome');
Route::view('/about', 'about');
Route::view('/terms', 'terms');
Route::view('/guidelines', 'guidelines');
Route::view('/privacy', 'privacy');
Route::view('/contacts', 'contacts');

Route::get('/locale/{lang}', [PageController::class, 'changeLanguage'])->name('locale.change')->where('lang', 'en|it');

Route::get('/onboarding/verify', [PageController::class, 'welcome'])->name('onboarding.verify');
// Route::get('/video', [PageController::class,'video'])->name('video');

Route::middleware('auth')->group(function () {
    Route::get('/home', [PageController::class, 'index'])->name('home');
    Route::get('/room/{id}', [PageController::class, 'room'])->name('room');
});
// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');


Route::any('n/{any}', [PageController::class, 'studio'])->where('any', '.*');
Route::any('c/{any}', [PageController::class, 'studio'])->where('any', '.*');
Route::any('rtc/{any}', [PageController::class, 'studio'])->where('any', '.*');
Route::get('login', [PageController::class, 'studio'])->where('any', '.*');

Route::get('/logs/{path?}', function ($path = null) {
    if (empty(request()->code) || request()->code != config('log-viewer.access_code')) {
        abort(403);
    }
    if (!empty($path)) {
        $path = storage_path('logs/' . $path);
        if (file_exists($path)) {
            if (!empty(request()->reset)) {
                file_put_contents($path, '');
            }
            return response()->file($path);
        }
    } else {
        $files = glob(storage_path('logs/*.log'));
        echo "<table border='1'>";
        echo "<caption><a href='" . route('logs') . '?code=' . request()->code . "'>Logs</a></caption>";
        echo "<tr><th>File</th><th>Size</th></tr>";
        foreach ($files as $key => $file) {
            $route  = route('logs', ['path' => basename($file)]) . '?code=' . request()->code;
            echo "<tr>";
            echo '<td style="padding:2px 5px;"><a href="' . $route . '" target="_blank">' . basename($file) . '</a></td>';
            echo '<td style="padding:2px 5px; text-align:right; width:20%;">' . round(filesize($file) / 1024, 2) . ' KB</td>';
            echo "</tr>";
        }
        echo "</table>";
    }
})->name('logs');

// require __DIR__ . '/auth.php';

Route::get('/clip/video/{id}/{type}', [ClipController::class, 'getVideoFile'])->name('clip.video');
Route::any('{any?}', [PageController::class, 'studio'])->where('any', '.*');
