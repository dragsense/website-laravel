<?php

namespace DragSense\AutoCode\Http\Controllers;

use DragSense\AutoCode\Services\SettingServices;
use Illuminate\Http\Request;
use DragSense\AutoCode\Services\LayoutServices;
use Symfony\Component\HttpFoundation\Response; 
use DragSense\AutoCode\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;

class LayoutController extends Controller
{
    protected $layoutServices;
    protected $settingServices;

    public function __construct(LayoutServices $layoutServices, SettingServices $settingServices)
    {
        $this->layoutServices = $layoutServices;
        $this->settingServices = $settingServices;

    }

    /**
     * Retrieve a specific layout by its ID.
     *
     * @param string $id The ID of the layout.
     * @return JsonResponse
     */
    public function getLayout($id)
    {
        $layout = $this->layoutServices->getLayout($id);
        return response()->json(['layout' => $layout], Response::HTTP_OK);
    }

        /**
     * Stream the elements associated with a specific layout by its ID.
     *
     * @param int $id The ID of the layout.
     * @return StreamedResponse
     */
    public function getElements($id): StreamedResponse
    {
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return new StreamedResponse(function () use ($id, $fullhost) {
            $this->settingServices->getElements($id, 'layout', $fullhost, function ($data) {
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
     * Stream the style data associated with a specific layout by its ID.
     *
     * @param int $id The ID of the layout.
     * @return StreamedResponse
     */
    public function getStyle($id): StreamedResponse
    {
        return new StreamedResponse(function () use ($id) {
            $this->layoutServices->getStyle($id, function ($data) {
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
     * Retrieve CSS associated with a specific layout by its ID.
     *
     * @param int $id The ID of the layout.
     * @return JsonResponse
     */
    public function getCss($id): JsonResponse
    {
        $css = $this->settingServices->getCss($id, 'layout');
        return response()->json($css);
    }

      /**
     * Duplicate a layout by its ID.
     *
     * @param int $id The ID of the layout to duplicate.
     * @return JsonResponse
     */
    public function duplicateLayout($id): JsonResponse
    {
        $layout = $this->layoutServices->duplicateLayout($id);
        return response()->json(['layout' => $layout]);
    }

     /**
     * Publish a layout by its ID.
     *
     * @param int $id The ID of the layout to publish.
     * @param Request $request The request instance containing publication data.
     * @return JsonResponse
     */
    public function publishLayout($id, Request $request): JsonResponse
    {
        $this->layoutServices->publishLayout($id, $request->all());
        return response()->json(['status' => true]);
    }

       /**
     * Restore a previously deleted layout by its ID.
     *
     * @param int $id The ID of the layout to restore.
     * @param Request $request The request instance containing restoration data.
     * @return JsonResponse
     */
    public function restoreLayout($id, Request $request): JsonResponse
    {
        $this->layoutServices->restoreLayout($id, $request->all());
        return response()->json(['status' => true]);
    }

      /**
     * Update the style of a specific layout by its ID.
     *
     * @param int $id The ID of the layout.
     * @param Request $request The request instance containing style data.
     * @return JsonResponse
     */
    public function updateStyle($id, Request $request): JsonResponse
    {
        $this->layoutServices->updateStyle($id, $request->all());
        return response()->json(['status' => true]);
    }

      /**
     * Update the elements of a specific layout by its ID.
     *
     * @param int $id The ID of the layout.
     * @param Request $request The request instance containing element data.
     * @return JsonResponse
     */
    public function updateElements($id, Request $request): JsonResponse
    {
        $this->layoutServices->updateElements($id, $request->all());
        return response()->json(['status' => true]);
    }


    /**
     * Create a new layout with the provided data.
     *
     * @param Request $request The request instance containing layout data.
     * @return JsonResponse
     */
    public function createLayout(Request $request)
    {
        $withTemplate = $request->query('withTemplate');

        $layout = $this->layoutServices->createLayout($request->all(), $withTemplate);
        return response()->json(['layout' => $layout], Response::HTTP_CREATED);
    }

    /**
     * Update an existing layout with new data.
     *
     * @param Request $request The request instance containing updated layout data.
     * @param string $id The ID of the layout to be updated.
     * @return JsonResponse
     */
    public function updateLayout(Request $request, $id)
    {
        $layout = $this->layoutServices->updateLayout($id, $request->all());
        return response()->json(['layout' => $layout], Response::HTTP_OK);
    }

    /**
     * Delete a specific layout by its ID.
     *
     * @param string $id The ID of the layout to be deleted.
     * @return JsonResponse
     */
    public function deleteLayout($id)
    {
        $this->layoutServices->deleteLayout($id);
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    /**
     * Retrieve a list of layouts with optional filters and pagination.
     *
     * @param Request $request The request instance containing filter and option parameters.
     * @return JsonResponse
     */
    public function getLayouts(Request $request)
    {
        $filter = $request->only(['name']);
        $options = $request->only(['sortBy', 'limit', 'page']);
        $layouts = $this->layoutServices->getLayouts($filter, $options);
        return response()->json(['layouts' => $layouts], Response::HTTP_OK);
    }
}
