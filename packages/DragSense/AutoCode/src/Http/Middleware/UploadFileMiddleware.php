<?php

namespace DragSense\AutoCode\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Symfony\Component\HttpFoundation\Response;

class UploadFileMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $maxSize = 50 * 1024 * 1024; // 50MB

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'file' => [
                'required',
                'file',
                'mimes:apng,avif,gif,jpeg,png,svg,webp,vnd.microsoft.icon,x-icon,avi,mpeg,mp4,ogg,3gpp2,3gpp,arc,octet-stream,msword,wordprocessingml,ms-fontobject,gzip,pdf,powerpoint,presentationml,spreadsheetml,xml,csv,css,html,javascript,mpeg,3gpp,ogg,3gpp2,wav,webm,otf,ttf,woff,woff2',
                'max:' . $maxSize,
            ],
            'type' => 'sometimes|in:images,videos,audios,fonts,docs',
        ]);

        if ($validator->fails()) {
            throw new ApiError($validator->errors()->first(), Response::HTTP_BAD_REQUEST);
        }

        $file = $request->file('file');
        $type = $request->query('type', 'temp');
        $allowedTypes = ['images', 'videos', 'audios', 'fonts', 'docs'];

        if (!in_array($type, $allowedTypes)) {
            throw new ApiError('Invalid file type. Allowed types are: images, videos, audios, and fonts.', 401);
        }

        // Define the upload path
        $uploadPath = "public/static/uploads/{$type}";

        // Check if the directory exists, if not create it
        if (!File::exists(storage_path('app/' . $uploadPath))) {
            File::makeDirectory(storage_path('app/' . $uploadPath), 0755, true);
        }

        // Store the file
        $filename = $file->hashName();
        $file->storeAs($uploadPath, $filename);

        // Add the file path to the request for further processing
        $request->merge(['filePath' => "static/uploads/{$type}/" . $filename]);

        return $next($request);
    }
}
