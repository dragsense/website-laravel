<?php

namespace DragSense\AutoCode\Routes\V1;

use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\DocumentController;

class DocumentRoutes
{
    public static function register()
    {
        Route::prefix('{collectionId}')->group(function () {
            Route::post('/', [DocumentController::class, 'createDocument']);
            Route::post('/duplicate/{id}', [DocumentController::class, 'duplicateDocument']);
            Route::get('/', [DocumentController::class, 'getDocuments']);
            Route::get('/{id}', [DocumentController::class, 'getDocument']);
            Route::put('/{id}', [DocumentController::class, 'updateDocument']);
            Route::delete('/{id}', [DocumentController::class, 'deleteDocument']);

            Route::post("/get/filtered", [DocumentController::class, "getFilteredDocuments"]);
            Route::put("/get/filtered", [DocumentController::class, "getAllFilteredDocuments"]);
        });
    }
}
