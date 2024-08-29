<?php

namespace DragSense\AutoCode\Routes\V1;


use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\BackupController;

class BackupRoutes
{
    public static function register()
    {
        Route::post('/', [BackupController::class, 'createBackup']);
        Route::get('/{id}', [BackupController::class, 'getBackup']);
        Route::put('/{id}', [BackupController::class, 'updateBackup']);
        Route::delete('/{id}', [BackupController::class, 'deleteBackup']);
        Route::put('/install/{id}', [BackupController::class, 'installBackup']);
    }
}
