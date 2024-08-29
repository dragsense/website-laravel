<?php

namespace DragSense\AutoCode\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use DragSense\AutoCode\Services\DocumentServices;
use DragSense\AutoCode\Services\SettingServices;

class DocumentController extends Controller
{
    protected $documentServices;
    protected $settingServices;

    public function __construct(DocumentServices $documentServices, SettingServices $settingServices)
    {
        $this->documentServices = $documentServices;
        $this->settingServices = $settingServices;
    }

    /**
     * Retrieve a document by its ID within a specified collection.
     *
     * @param Request $request The request instance.
     * @param string $collectionId The ID of the collection.
     * @param string $id The ID of the document.
     * @return JsonResponse
     */
    public function getDocument(Request $request, $collectionId, $id): JsonResponse
    {
        $isForm = $request->query('form') === 'true';
        $document = $this->documentServices->getDocument($collectionId, $isForm, $id);

        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return response()->json(['document' => $document, 'host' => $fullhost]);
    }

    /**
     * Create a new document in a specified collection.
     *
     * @param Request $request The request instance containing document data.
     * @param string $collectionId The ID of the collection.
     * @return JsonResponse
     */
    public function createDocument(Request $request, $collectionId): JsonResponse
    {
        $isForm = $request->query('form') === 'true';
        $document = $this->documentServices->createDocument($collectionId, $isForm, $request->all());

        return response()->json(['document' => $document], 201);
    }

    /**
     * Duplicate an existing document within a specified collection.
     *
     * @param Request $request The request instance.
     * @param string $collectionId The ID of the collection.
     * @param string $id The ID of the document to be duplicated.
     * @return JsonResponse
     */
    public function duplicateDocument(Request $request, $collectionId, $id): JsonResponse
    {
        $isForm = $request->query('form') === 'true';
        $document = $this->documentServices->duplicateDocument($collectionId, $isForm, $id);

        return response()->json(['document' => $document]);
    }

    /**
     * Update an existing document within a specified collection.
     *
     * @param Request $request The request instance containing updated document data.
     * @param string $collectionId The ID of the collection.
     * @param string $id The ID of the document to be updated.
     * @return JsonResponse
     */
    public function updateDocument(Request $request, $collectionId, $id): JsonResponse
    {
        $isForm = $request->query('form') === 'true';
        $document = $this->documentServices->updateDocument($collectionId, $isForm, $id, $request->all());

        return response()->json(['document' => $document]);
    }

    /**
     * Delete a document from a specified collection.
     *
     * @param Request $request The request instance.
     * @param string $collectionId The ID of the collection.
     * @param string $id The ID of the document to be deleted.
     * @return JsonResponse
     */
    public function deleteDocument(Request $request, $collectionId, $id): JsonResponse
    {
        $isForm = $request->query('form') === 'true';
        $this->documentServices->deleteDocument($collectionId, $isForm, $id);

        return response()->json(null, 204);
    }

    /**
     * Retrieve a list of documents within a specified collection, with optional filters and options.
     *
     * @param Request $request The request instance containing filter and option parameters.
     * @param string $collectionId The ID of the collection.
     * @return JsonResponse
     */
    public function getDocuments(Request $request, $collectionId): JsonResponse
    {
        $isForm = $request->query('form') === 'true';
        $filter = $request->only(['name']);
        $options = $request->only(['sortBy', 'limit', 'page']);
        $documents = $this->documentServices->getDocuments($collectionId, $isForm, $filter, $options);

        return response()->json(['documents' => $documents]);
    }

    /**
     * Retrieve all documents with filters applied, within a specified collection.
     *
     * @param Request $request The request instance containing filter and option parameters.
     * @param string $collectionId The ID of the collection.
     * @return JsonResponse
     */
    public function getAllFilteredDocuments(Request $request, $collectionId): JsonResponse
    {
        $filter = $request->only(['name']);
        $options = $request->only(['sortBy', 'limit', 'page', 'status']);
        $results = $this->documentServices->getAllFilteredDocuments($collectionId, $request->all(), $filter, $options);

        return response()->json($results, 201);
    }

    /**
     * Retrieve filtered documents within a specified collection with options.
     *
     * @param Request $request The request instance containing option parameters.
     * @param string $collectionId The ID of the collection.
     * @return JsonResponse
     */
    public function getFilteredDocuments(Request $request, $collectionId): JsonResponse
    {
        $options = $request->only(['sortBy', 'limit', 'page', 'status']);
        $results = $this->documentServices->getFilteredDocuments($collectionId, $request->all(), $options);

        return response()->json($results, 201);
    }
}
