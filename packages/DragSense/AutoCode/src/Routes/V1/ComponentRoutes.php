<?php

namespace DragSense\AutoCode\Routes\V1;

use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\ComponentController;

class ComponentRoutes
{
    public static function register()
    {
        Route::post('/', [ComponentController::class, 'createComponent']);

        Route::get('/', [ComponentController::class, 'getComponents']);

        Route::get('/custom/paths', [ComponentController::class, 'getPaths']);

        Route::get('/elements/{id}', [ComponentController::class, 'getElements']);

        Route::get('/style/{id}', [ComponentController::class, 'getStyle']);

        Route::get('/css/{id}', [ComponentController::class, 'getCss']);

        Route::post('/duplicate/{id}', [ComponentController::class, 'duplicateComponent']);

        Route::get('/{id}', [ComponentController::class, 'getComponent']);

        Route::post('/elements/{id}', [ComponentController::class, 'updateElements']);

        Route::post('/styles/{id}', [ComponentController::class, 'updateStyle']);

        Route::post('/publish/{id}', [ComponentController::class, 'publishComponent']);

        Route::post('/restore/{id}', [ComponentController::class, 'restoreComponent']);

        Route::put('/{id}', [ComponentController::class, 'updateComponent']);

        Route::delete('/{id}', [ComponentController::class, 'deleteComponent']);
    }
}
