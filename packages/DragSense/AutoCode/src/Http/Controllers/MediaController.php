<?php

namespace DragSense\AutoCode\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DragSense\AutoCode\Services\MediaServices;
use Illuminate\Support\Facades\Response;
use DragSense\AutoCode\Http\Controllers\Controller;

class MediaController extends Controller
{
    protected $mediaServices;

    public function __construct(MediaServices $mediaServices)
    {
        $this->mediaServices = $mediaServices;
        // Apply middleware for file upload only to the createMedia method
        $this->middleware('autocode.upload.file')->only('createMedia');
    }

    /**
     * Create new media from an uploaded file.
     *
     * @param Request $request The request instance containing file and metadata.
     * @return JsonResponse
     */
    public function createMedia(Request $request)
    {
        $file = $request->file('file'); // Retrieve the uploaded file
        $type = $request->query('type'); // Retrieve the media type from query parameters
        $filePath = $request->input('filePath'); // Retrieve the file path from input data

        // Call service to handle media creation
        $media = $this->mediaServices->createMedia($file, $type, $filePath);

        // Construct the full host URL
        $protocol = $request->getScheme() ?? 'http';
        $host = $request->header('Host');
        $fullhost = "{$protocol}://{$host}";

        // Return response with media details and host URL
        return Response::json(['media' => $media, 'host' => $fullhost], 201);
    }

    /**
     * Update existing media with new data.
     *
     * @param string $id The ID of the media to be updated.
     * @param Request $request The request instance containing updated media data.
     * @return JsonResponse
     */
    public function updateMedia($id, Request $request)
    {
        // Call service to handle media update
        $media = $this->mediaServices->updateMedia($id, $request->all());
        return Response::json(['media' => $media], 200);
    }

    /**
     * Delete media by its ID.
     *
     * @param string $id The ID of the media to be deleted.
     * @return JsonResponse
     */
    public function deleteMedia($id)
    {
        // Call service to handle media deletion
        $this->mediaServices->deleteMedia($id);
        return response()->json([], 204);
    }

    /**
     * Retrieve media with optional filters and pagination.
     *
     * @param Request $request The request instance containing filter and pagination parameters.
     * @return JsonResponse
     */
    public function getMedia(Request $request)
    {
        $filter = $request->only(['name', 'type']); // Retrieve filter parameters
        $options = $request->only(['sortBy', 'limit', 'page']); // Retrieve pagination options

        // Call service to get media list
        $media = $this->mediaServices->getMedia($filter, $options);

        // Construct the full host URL
        $protocol = $request->getScheme() ?? 'http';
        $host = $request->header('Host');
        $fullhost = "{$protocol}://{$host}";

        // Return response with media list and host URL
        return response()->json(['media' => $media, 'host' => $fullhost], 200);
    }
}
