<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\Api\AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [\App\Http\Controllers\Api\AuthController::class, 'getUserDetails']);
    Route::post('/me/update', [\App\Http\Controllers\Api\AuthController::class, 'updateProfile']);
    Route::post('/logout', [\App\Http\Controllers\Api\AuthController::class, 'logout']);

    Route::group(['prefix' => 'studio'], function () {
        Route::group(['prefix' => 'rooms'], function () {
            Route::get('/list', [\App\Http\Controllers\Api\Studio\RoomController::class, 'index']);
            Route::post('/store', [\App\Http\Controllers\Api\Studio\RoomController::class, 'store']);
            Route::get('/{id}/show', [\App\Http\Controllers\Api\Studio\RoomController::class, 'show']);
            Route::get('/{code}/code', [\App\Http\Controllers\Api\Studio\RoomController::class, 'roomByCode'])->withoutMiddleware('auth:sanctum');
            Route::post('/auth', [\App\Http\Controllers\Api\Studio\RoomController::class, 'roomAuthorization'])->withoutMiddleware('auth:sanctum');
            Route::post('/{id}/update', [\App\Http\Controllers\Api\Studio\RoomController::class, 'update']);
            Route::delete('/{id}', [\App\Http\Controllers\Api\Studio\RoomController::class, 'destroy']);
            Route::group(['prefix' => 'config'], function () {
                Route::get('/{roomId}/list', [\App\Http\Controllers\Api\Studio\RoomController::class, 'config'])->withoutMiddleware('auth:sanctum');
                Route::post('/store', [\App\Http\Controllers\Api\Studio\RoomController::class, 'storeConfig']);

            });
            Route::group(['prefix' => '{slug}/chats'], function () {
                Route::withoutMiddleware('auth:sanctum')->group(function () {
                    Route::get('/list', [\App\Http\Controllers\Api\Studio\ChatController::class, 'index']);
                    Route::post('/store', [\App\Http\Controllers\Api\Studio\ChatController::class, 'store']);
                    Route::delete('/destroy', [\App\Http\Controllers\Api\Studio\ChatController::class, 'destroy']);
                    Route::get('/export', [\App\Http\Controllers\Api\Studio\ChatController::class, 'export']);
                });
            });
        });
       
    });

    Route::group(['prefix' => 'clip'], function () {
        Route::get('/list', [\App\Http\Controllers\Api\Clip\ClipController::class, 'index']);
        Route::post('/store', [\App\Http\Controllers\Api\Clip\ClipController::class, 'store']);
        //Route::get('/{id}/show', [\App\Http\Controllers\Api\Clip\ClipController::class, 'show']);
        Route::post('/{id}/update', [\App\Http\Controllers\Api\Clip\ClipController::class, 'update']);
        Route::delete('/{id}/destroy', [\App\Http\Controllers\Api\Clip\ClipController::class, 'destroy']);
        
        // Dual stream upload endpoints
        Route::post('/upload-streams', [\App\Http\Controllers\Api\ClipStreamController::class, 'uploadStreams']);
        Route::get('/{clipId}/processing-status', [\App\Http\Controllers\Api\ClipStreamController::class, 'getProcessingStatus']);
        Route::get('/{clipId}/stream-metadata', [\App\Http\Controllers\Api\ClipStreamController::class, 'getStreamMetadata']);
        
        Route::group(['prefix' => 'comment'], function () {
            //Route::get('/list', [\App\Http\Controllers\Api\Clip\CommentController::class, 'index']);
            Route::post('/store', [\App\Http\Controllers\Api\Clip\CommentController::class, 'store']);
            Route::get('/{id}/show', [\App\Http\Controllers\Api\Clip\CommentController::class, 'show']);
            Route::post('/{id}/update', [\App\Http\Controllers\Api\Clip\CommentController::class, 'update']);
            Route::delete('/{id}/destroy', [\App\Http\Controllers\Api\Clip\CommentController::class, 'destroy']);    
        });
    });
    
});

Route::group(['prefix' => 'clip'], function () {
    Route::get('/{slug}/show', [\App\Http\Controllers\Api\Clip\ClipController::class, 'show']);
    Route::post('/check-viewer', [\App\Http\Controllers\Api\Clip\ClipController::class, 'trackViewer']);
    Route::group(['prefix' => 'comment'], function () {
        Route::get('/{clipId}/list', [\App\Http\Controllers\Api\Clip\CommentController::class, 'index']);
    });
});
