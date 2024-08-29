<?php

namespace DragSense\AutoCode\Routes\V1;

use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\PageController;

class PageRoutes
{
    public static function register()
    {
        Route::post('/', [PageController::class, 'createPage']);

        Route::get('/', [PageController::class, 'getPages']);

        Route::post('/duplicate/{id}', [PageController::class, 'duplicatePage']);

        Route::get('/{id}', [PageController::class, 'getPage']);

        Route::get('/elements/{id}', [PageController::class, 'getElements']);

        Route::get('/style/{id}', [PageController::class, 'getStyle']);

        Route::get('/css/{id}', [PageController::class, 'getCss']);

        Route::put('/{id}', [PageController::class, 'updatePage']);

        Route::post('/elements/{id}', [PageController::class, 'updateElements']);

        Route::post('/publish/{id}', [PageController::class, 'publishPage']);

        Route::post('/restore/{id}', [PageController::class, 'restorePage']);

        Route::post('/styles/{id}', [PageController::class, 'updateStyle']);

        Route::delete('/{id}', [PageController::class, 'deletePage']);
    }
}
