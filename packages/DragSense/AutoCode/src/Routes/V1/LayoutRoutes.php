<?php

namespace DragSense\AutoCode\Routes\V1;

use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\LayoutController;

class LayoutRoutes
{
    public static function register()
    {
        Route::post('/', [LayoutController::class, 'createLayout']);

        Route::get('/', [LayoutController::class, 'getLayouts']);

        Route::post('/duplicate/{id}', [LayoutController::class, 'duplicateLayout']);

        Route::get('/{id}', [LayoutController::class, 'getLayout']);

        Route::get('/elements/{id}', [LayoutController::class, 'getElements']);

        Route::get('/style/{id}', [LayoutController::class, 'getStyle']);

        Route::get('/css/{id}', [LayoutController::class, 'getCss']);

        Route::put('/{id}', [LayoutController::class, 'updateLayout']);

        Route::post('/elements/{id}', [LayoutController::class, 'updateElements']);

        Route::post('/publish/{id}', [LayoutController::class, 'publishLayout']);

        Route::post('/restore/{id}', [LayoutController::class, 'restoreLayout']);

        Route::post('/styles/{id}', [LayoutController::class, 'updateStyle']);

        Route::delete('/{id}', [LayoutController::class, 'deleteLayout']);
    }
}
