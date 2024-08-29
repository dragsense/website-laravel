<?php

namespace DragSense\AutoCode\Routes\V1;

use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\CollectionController;

class CollectionRoutes
{
    public static function register()
    {
        Route::post('/', [CollectionController::class, 'createCollection']);

        Route::get('/', [CollectionController::class, 'getCollections']);

        Route::post('/duplicate/{id}', [CollectionController::class, 'duplicateCollection']);

        Route::get('/{id}', [CollectionController::class, 'getCollection']);

        Route::get('/elements/{id}', [CollectionController::class, 'getElements']);

        Route::get('/style/{id}', [CollectionController::class, 'getStyle']);

        Route::get('/css/{id}', [CollectionController::class, 'getCss']);

        Route::put('/{id}', [CollectionController::class, 'updateCollection']);

        Route::post('/elements/{id}', [CollectionController::class, 'updateElements']);

        Route::post('/publish/{id}', [CollectionController::class, 'publishCollection']);

        Route::post('/restore/{id}', [CollectionController::class, 'restoreCollection']);

        Route::post('/styles/{id}', [CollectionController::class, 'updateStyle']);

        Route::delete('/{id}', [CollectionController::class, 'deleteCollection']);
    }
}
