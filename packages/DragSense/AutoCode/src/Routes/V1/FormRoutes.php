<?php

namespace DragSense\AutoCode\Routes\V1;

use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\FormController;

class FormRoutes
{
    public static function register()
    {
        Route::post('/', [FormController::class, 'createForm']);
        Route::get('/', [FormController::class, 'getForms']);

        Route::get('/elements/{id}', [FormController::class, 'getElements']);
        Route::get('/style/{id}', [FormController::class, 'getStyle']);
        Route::get('/css/{id}', [FormController::class, 'getCss']);

        Route::post('/duplicate/{id}', [FormController::class, 'duplicateForm']);
        Route::get('/{id}', [FormController::class, 'getForm']);
        Route::put('/{id}', [FormController::class, 'updateForm']);
        Route::post('/elements/{id}', [FormController::class, 'updateElements']);
        Route::post('/styles/{id}', [FormController::class, 'updateStyle']);
        Route::post('/publish/{id}', [FormController::class, 'publishForm']);
        Route::post('/restore/{id}', [FormController::class, 'restoreForm']);

        Route::delete('/{id}', [FormController::class, 'deleteForm']);
    }
}
