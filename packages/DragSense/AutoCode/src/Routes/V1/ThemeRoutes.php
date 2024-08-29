<?php

namespace DragSense\AutoCode\Routes\V1;


use Illuminate\Support\Facades\Route;
use DragSense\AutoCode\Http\Controllers\ThemeController;

class ThemeRoutes
{
    public static function register()
    {
        // Color routes
        Route::get('/colors', [ThemeController::class, 'getColors']);
        Route::post('/color', [ThemeController::class, 'createColor']);
        Route::put('/color/{_uid}', [ThemeController::class, 'updateColor']);
        Route::delete('/color/{_uid}', [ThemeController::class, 'deleteColor']);

        // Font routes
        Route::get('/fonts', [ThemeController::class, 'getFonts']);
        Route::post('/font', [ThemeController::class, 'createFont']);
        Route::put('/font/{_uid}', [ThemeController::class, 'updateFont']);
        Route::delete('/font/{_uid}', [ThemeController::class, 'deleteFont']);

        // Icon routes
        Route::get('/icons', [ThemeController::class, 'getIcons']);
        Route::post('/icon', [ThemeController::class, 'createIcon']);
        Route::put('/icon/{_uid}', [ThemeController::class, 'updateIcon']);
        Route::delete('/icon/{_uid}', [ThemeController::class, 'deleteIcon']);

        // Variable routes
        Route::get('/variables', [ThemeController::class, 'getVariables']);
        Route::post('/variable', [ThemeController::class, 'createVariable']);
        Route::put('/variable/{_uid}', [ThemeController::class, 'updateVariable']);
        Route::delete('/variable/{_uid}', [ThemeController::class, 'deleteVariable']);

        // Style routes
        Route::get('/style/{_id}', [ThemeController::class, 'getStyle']);
        Route::post('/style/{_id}', [ThemeController::class, 'saveStyle']);

        // Animation routes
        Route::get('/animation/{_id}', [ThemeController::class, 'getAnimations']);
        Route::post('/animation/{_id}', [ThemeController::class, 'saveAnimations']);

        // Animation names
        Route::get('/css/animation/names', [ThemeController::class, 'getAnimationsNames']);

        // CSS routes
        Route::get('/css/animation', [ThemeController::class, 'getAnimationsCss']);
        Route::get('/css', [ThemeController::class, 'getCss']);
        Route::get('/customCss', [ThemeController::class, 'getCustomCss']);
        Route::post('/css', [ThemeController::class, 'saveCss']);

        // JS routes
        Route::get('/js', [ThemeController::class, 'getJs']);
        Route::post('/js', [ThemeController::class, 'saveJs']);
    }
}
