<?php

namespace DragSense\AutoCode\Routes\V1;

use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\MediaController;

class MediaRoutes
{
    public static function register()
    {
        Route::post('/', [MediaController::class, 'createMedia']);

        Route::get('/', [MediaController::class, 'getMedia']);
        
        Route::put('/{id}', [MediaController::class, 'updateMedia']);


        Route::delete('/{id}', [MediaController::class, 'deleteMedia']);
    }
}
