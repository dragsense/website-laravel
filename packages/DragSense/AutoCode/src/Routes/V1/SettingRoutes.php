<?php

namespace DragSense\AutoCode\Routes\V1;


use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\SettingController;

class SettingRoutes
{
    public static function register()
    {
        Route::get('/', [SettingController::class, 'getSettings']);
        Route::post('/', [SettingController::class, 'saveSettings']);
    }
}
