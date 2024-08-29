<?php

namespace DragSense\AutoCode\Http\Controllers;

use DragSense\AutoCode\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DragSense\AutoCode\Services\PageServices;
use DragSense\AutoCode\Services\SettingServices;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    protected $pageServices;
    protected $settingServices;

    public function __construct(PageServices $pageServices, SettingServices $settingServices)
    {
        $this->pageServices = $pageServices;
        $this->settingServices = $settingServices;
    }

    /**
     * Retrieve a specific page by its ID.
     *
     * @param int $id The ID of the page to retrieve.
     * @return JsonResponse
     */
    public function getPage($id): JsonResponse
    {
        $page = $this->pageServices->getPage($id);
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return response()->json(['page' => $page, 'host' => $fullhost]);
    }

    /**
     * Retrieve page data for a specific page by its ID.
     *
     * @param int $id The ID of the page.
     * @return JsonResponse
     */
    public function getPageData($id): JsonResponse
    {
        $result = $this->pageServices->getPageData($id);
        return response()->json($result);
    }

    /**
     * Stream the elements associated with a specific page by its ID.
     *
     * @param int $id The ID of the page.
     * @return StreamedResponse
     */
    public function getElements($id): StreamedResponse
    {
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return new StreamedResponse(function () use ($id, $fullhost) {
            $this->settingServices->getElements($id, 'page', $fullhost, function ($data) {
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
     * Stream the style data associated with a specific page by its ID.
     *
     * @param int $id The ID of the page.
     * @return StreamedResponse
     */
    public function getStyle($id): StreamedResponse
    {
        return new StreamedResponse(function () use ($id) {
            $this->pageServices->getStyle($id, function ($data) {
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
     * Retrieve CSS associated with a specific page by its ID.
     *
     * @param int $id The ID of the page.
     * @return JsonResponse
     */
    public function getCss($id): JsonResponse
    {
        $css = $this->settingServices->getCss($id, 'page');
        return response()->json($css);
    }

    /**
     * Duplicate a page by its ID.
     *
     * @param int $id The ID of the page to duplicate.
     * @return JsonResponse
     */
    public function duplicatePage($id): JsonResponse
    {
        $page = $this->pageServices->duplicatePage($id);
        return response()->json(['page' => $page]);
    }

    /**
     * Create a new page with the provided data.
     *
     * @param Request $request The request instance containing page data.
     * @return JsonResponse
     */
    public function createPage(Request $request): JsonResponse
    {
        $page = $this->pageServices->createPage($request->all());
        return response()->json(['page' => $page], 201);
    }

    /**
     * Update an existing page by its ID with the provided data.
     *
     * @param int $id The ID of the page to update.
     * @param Request $request The request instance containing updated page data.
     * @return JsonResponse
     */
    public function updatePage($id, Request $request): JsonResponse
    {
        $page = $this->pageServices->updatePage($id, $request->all());
        return response()->json(['page' => $page]);
    }

    /**
     * Publish a page by its ID.
     *
     * @param int $id The ID of the page to publish.
     * @param Request $request The request instance containing publication data.
     * @return JsonResponse
     */
    public function publishPage($id, Request $request): JsonResponse
    {
        $this->pageServices->publishPage($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Restore a previously deleted page by its ID.
     *
     * @param int $id The ID of the page to restore.
     * @param Request $request The request instance containing restoration data.
     * @return JsonResponse
     */
    public function restorePage($id, Request $request): JsonResponse
    {
        $this->pageServices->restorePage($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update the style of a specific page by its ID.
     *
     * @param int $id The ID of the page.
     * @param Request $request The request instance containing style data.
     * @return JsonResponse
     */
    public function updateStyle($id, Request $request): JsonResponse
    {
        $this->pageServices->updateStyle($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update the elements of a specific page by its ID.
     *
     * @param int $id The ID of the page.
     * @param Request $request The request instance containing element data.
     * @return JsonResponse
     */
    public function updateElements($id, Request $request): JsonResponse
    {
        $this->pageServices->updateElements($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Delete a page by its ID.
     *
     * @param int $id The ID of the page to delete.
     * @return JsonResponse
     */
    public function deletePage($id): JsonResponse
    {
        $this->pageServices->deletePage($id);
        return response()->json(null, 204);
    }

    /**
     * Retrieve a list of pages with optional filters and pagination.
     *
     * @param Request $request The request instance containing filter and pagination parameters.
     * @return JsonResponse
     */
    public function getPages(Request $request): JsonResponse
    {
        $filter = $request->only(['name']);
        $options = $request->only(['sortBy', 'limit', 'page']);
        $pages = $this->pageServices->getPages($filter, $options);
        return response()->json(['pages' => $pages]);
    }
}
