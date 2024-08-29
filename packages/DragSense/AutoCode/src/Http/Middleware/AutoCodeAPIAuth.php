<?php

namespace DragSense\AutoCode\Http\Middleware;

use Closure;
use Symfony\Component\HttpFoundation\Response;
use DragSense\AutoCode\Http\Exceptions\ApiError;

class AutoCodeAPIAuth
{
    public function handle($request, Closure $next)
    {
        $apiKey = $request->header('x-api-key');

        // Assuming you have stored the API key in your package's config file
        if ($apiKey === config('autocode.api_key')) {
            return $next($request);
        }

        // Throwing an unauthorized error if the API key is invalid
        throw new ApiError('Please authenticate', Response::HTTP_UNAUTHORIZED,);

    }
}
