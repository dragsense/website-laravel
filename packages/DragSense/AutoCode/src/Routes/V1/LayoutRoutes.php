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

        Route::get('/{id}', [LayoutController::class, 'getLayout']);
        
        Route::put('/{id}', [LayoutController::class, 'updateLayout']);


        Route::delete('/{id}', [LayoutController::class, 'deleteLayout']);
    }
}
