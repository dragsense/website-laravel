<?php

use DragSense\AutoCode\Http\Controllers\DocumentController;
use DragSense\AutoCode\Http\Controllers\SettingController;
use DragSense\AutoCode\Http\Controllers\FormController;

use DragSense\AutoCode\Routes\V1\BackupRoutes;
use DragSense\AutoCode\Routes\V1\ComponentRoutes;
use DragSense\AutoCode\Routes\V1\DocumentRoutes;
use DragSense\AutoCode\Routes\V1\FormRoutes;
use DragSense\AutoCode\Routes\V1\PageRoutes;
use DragSense\AutoCode\Routes\V1\ThemeRoutes;
use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Routes\V1\SettingRoutes;
use DragSense\AutoCode\Routes\V1\LayoutRoutes;
use DragSense\AutoCode\Routes\V1\MediaRoutes;
use DragSense\AutoCode\Routes\V1\CollectionRoutes;



Route::middleware('autocode.api.auth')->prefix('v1')->group(function () {

    Route::get('/', function () {
        return response()->json(['status' => true]);
    });

    Route::prefix('layouts')->group(function () {
        LayoutRoutes::register();
    });


    Route::prefix('pages')->group(function () {
        PageRoutes::register();
    });

    Route::prefix('components')->group(function () {
        ComponentRoutes::register();
    });

    Route::prefix('collections')->group(function () {
        CollectionRoutes::register();
    });

    Route::prefix('forms')->group(function () {
        FormRoutes::register();
    });

    Route::prefix('documents')->group(function () {
        DocumentRoutes::register();
    });

    Route::prefix('settings')->group(function () {
        SettingRoutes::register();
    });

    Route::prefix('media')->group(function () {
        MediaRoutes::register();
    });

    Route::prefix('theme')->group(function () {
        ThemeRoutes::register();
    });

    Route::prefix('backup')->group(function () {
        BackupRoutes::register();
    });

});

Route::get('/v1/pages/data/{id}', [SettingController::class, 'getPageData']);

Route::post('/v1/forms/submit/{id}', [FormController::class, 'submitForm']);

Route::post("/v1/collection/:collectionId/document", [DocumentController::class, "getAllFilteredDocuments"]);
Route::put("/v1/collection/:collectionId/document", [DocumentController::class, "getFilteredDocuments"]);

