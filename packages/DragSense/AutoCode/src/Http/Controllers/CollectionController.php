<?php

namespace DragSense\AutoCode\Http\Controllers;

use DragSense\AutoCode\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DragSense\AutoCode\Services\CollectionServices;
use DragSense\AutoCode\Services\SettingServices;
use Illuminate\Http\JsonResponse;

class CollectionController extends Controller
{
    // Dependency injection for CollectionServices and SettingServices
    protected $collectionServices;
    protected $settingServices;

    public function __construct(CollectionServices $collectionServices, SettingServices $settingServices)
    {
        $this->collectionServices = $collectionServices;
        $this->settingServices = $settingServices;
    }

    /**
     * Retrieve a collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @return JsonResponse
     */
    public function getCollection($id): JsonResponse
    {
        $collection = $this->collectionServices->getCollection($id);
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return response()->json(['collection' => $collection, 'host' => $fullhost]);
    }

    /**
     * Retrieve the data of a collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @return JsonResponse
     */
    public function getCollectionData($id): JsonResponse
    {
        $result = $this->collectionServices->getCollectionData($id);
        return response()->json($result);
    }

    /**
     * Stream the elements of a collection.
     *
     * @param string $id The ID of the collection.
     * @return StreamedResponse
     */
    public function getElements($id): StreamedResponse
    {
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return new StreamedResponse(function () use ($id, $fullhost) {
            $this->settingServices->getElements($id, 'collection', $fullhost, function ($data) {
                if ($data !== null && $data !== '') {
                    $chunkSize = dechex(strlen($data));
                    echo "{$chunkSize}\r\n{$data}\r\n";
                    flush();
                }
            });

            // Properly terminate the chunked response
            echo "0\r\n\r\n";
            flush();
        }, 200, [
            'Content-Type' => 'application/json',
            'Transfer-Encoding' => 'chunked',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Stream the style information of a collection.
     *
     * @param string $id The ID of the collection.
     * @return StreamedResponse
     */
    public function getStyle($id): StreamedResponse
    {
        return new StreamedResponse(function () use ($id) {
            $this->collectionServices->getStyle($id, function ($data) {
                if ($data !== null && $data !== '') {
                    $chunkSize = dechex(strlen($data));
                    echo "{$chunkSize}\r\n{$data}\r\n";
                    flush();
                }
            });

            // Properly terminate the chunked response
            echo "0\r\n\r\n";
            flush();
        }, 200, [
            'Content-Type' => 'application/json',
            'Transfer-Encoding' => 'chunked',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Retrieve the CSS for a collection.
     *
     * @param string $id The ID of the collection.
     * @return JsonResponse
     */
    public function getCss($id): JsonResponse
    {
        $css = $this->settingServices->getCss($id, 'collection');
        return response()->json($css);
    }

    /**
     * Duplicate a collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @return JsonResponse
     */
    public function duplicateCollection($id): JsonResponse
    {
        $collection = $this->collectionServices->duplicateCollection($id);
        return response()->json(['collection' => $collection]);
    }

    /**
     * Create a new collection.
     *
     * @param Request $request The request containing collection data.
     * @return JsonResponse
     */
    public function createCollection(Request $request): JsonResponse
    {
        $collection = $this->collectionServices->createCollection($request->all());
        return response()->json(['collection' => $collection], 201);
    }

    /**
     * Update an existing collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @param Request $request The request containing updated data.
     * @return JsonResponse
     */
    public function updateCollection($id, Request $request): JsonResponse
    {
        $collection = $this->collectionServices->updateCollection($id, $request->all());
        return response()->json(['collection' => $collection]);
    }

    /**
     * Publish a collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @param Request $request The request containing publication data.
     * @return JsonResponse
     */
    public function publishCollection($id, Request $request): JsonResponse
    {
        $this->collectionServices->publishCollection($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Restore a deleted collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @param Request $request The request containing restoration data.
     * @return JsonResponse
     */
    public function restoreCollection($id, Request $request): JsonResponse
    {
        $this->collectionServices->restoreCollection($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update the style of a collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @param Request $request The request containing style data.
     * @return JsonResponse
     */
    public function updateStyle($id, Request $request): JsonResponse
    {
        $this->collectionServices->updateStyle($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update the elements of a collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @param Request $request The request containing elements data.
     * @return JsonResponse
     */
    public function updateElements($id, Request $request): JsonResponse
    {
        $this->collectionServices->updateElements($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Delete a collection by its ID.
     *
     * @param string $id The ID of the collection.
     * @return JsonResponse
     */
    public function deleteCollection($id): JsonResponse
    {
        $this->collectionServices->deleteCollection($id);
        return response()->json(null, 204);
    }

    /**
     * Retrieve a list of collections with optional filters and options.
     *
     * @param Request $request The request containing filter and option parameters.
     * @return JsonResponse
     */
    public function getCollections(Request $request): JsonResponse
    {
        $filter = $request->only(['name']);
        $options = $request->only(['sortBy', 'limit', 'collection']);
        $collections = $this->collectionServices->getCollections($filter, $options);
        return response()->json(['collections' => $collections]);
    }
}
