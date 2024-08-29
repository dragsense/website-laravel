<?php

namespace DragSense\AutoCode\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use View;

class AutoCodeServiceProvider extends ServiceProvider
{
    public function boot()
    {

        View::addNamespace('autocode', __DIR__ . '/../Views');


        // Publish configuration file
        $this->publishes([
            base_path('config/autocode.php') => config_path('autocode.php'),
        ], 'config');

        // Load the API prefix from the .env file
        $prefix = config('autocode.prefix', 'api');

        // Load the AutoCode routes with the specified prefix
        Route::prefix($prefix)
            ->middleware('api') // Apply the default 'api' middleware group
            ->group(__DIR__ . '/../Routes/api.php');
    }

    public function register()
    {
        // Register middleware
        $this->app['router']->aliasMiddleware('autocode.api.auth', \DragSense\AutoCode\Http\Middleware\AutoCodeAPIAuth::class);
        $this->app['router']->aliasMiddleware('autocode.upload.file', \DragSense\AutoCode\Http\Middleware\UploadFileMiddleware::class);

    }
}
