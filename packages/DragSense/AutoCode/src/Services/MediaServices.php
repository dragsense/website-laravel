<?php

namespace DragSense\AutoCode\Services;

use DragSense\AutoCode\Models\Media;
use Illuminate\Support\Facades\File;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Symfony\Component\HttpFoundation\Response;

class MediaServices
{
    protected $publicDir;

    public function __construct()
    {
        $uploadPath = "static/uploads";
        $this->publicDir = storage_path('app/public/' . $uploadPath);

        if (!File::exists($this->publicDir)) {
            File::makeDirectory($this->publicDir, 0755, true);
        }

    }

    /**
     * Create a media
     * @param \Illuminate\Http\UploadedFile $file
     * @param string $type
     * @return Media
     */
    public function createMedia($file, $type, $filePath)
    {
        $fileSizeInBytes = $file->getSize();

        $dimensions = [];
        if ($type === 'images') {
            $size = getimagesize($file->getRealPath());
            if ($size) {
                $dimensions = [
                    'width' => $size[0],  // Width of the image
                    'height' => $size[1], // Height of the image
                ];
            }

        }

        $filename = $file->hashName();

        $mediaBody = [
            'src' => "/storage/{$filePath}",
            'fileName' => $filename,
            'name' => $file->getClientOriginalName(),
            'type' => $type,
            'mimetype' => $file->getMimeType(),
            'alt' => $file->getClientOriginalName(),
            'size' => $fileSizeInBytes,
            'dimensions' => $dimensions,
        ];

        return Media::create($mediaBody);
    }

    /**
     * Update a media
     * @param string $id
     * @param array $mediaBody
     * @return Media
     */
    public function updateMedia($id, array $mediaBody)
    {
        $media = Media::find($id);

        if (!$media) {
            throw new ApiError('Media not found', Response::HTTP_NOT_FOUND);
        }

        $media->update($mediaBody);
        return $media;
    }

    /**
     * Get Media
     * @param array $filter
     * @param array $options
     * @return  mixed     
     */
    public function getMedia(array $filter, array $options)
    {
        return Media::paginate($filter, $options);
    }

    /**
     * Delete a media
     * @param string $id
     * @return string
     */
    public function deleteMedia($id)
    {
        $media = Media::find($id);

        if (!$media) {
            throw new ApiError('Media not found', Response::HTTP_NOT_FOUND);
        }

        $filePath = "{$this->publicDir}/{$media->type}/{$media->fileName}";

        if (File::exists($filePath)) {
            File::delete($filePath);
        }

        $media->delete();
        return $id;
    }
}
