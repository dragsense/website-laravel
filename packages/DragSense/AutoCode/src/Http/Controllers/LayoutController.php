<?php

namespace DragSense\AutoCode\Http\Controllers;

use Illuminate\Http\Request;
use DragSense\AutoCode\Services\LayoutServices;
use Symfony\Component\HttpFoundation\Response; 
use DragSense\AutoCode\Http\Controllers\Controller;

class LayoutController extends Controller
{
    protected $layoutServices;

    public function __construct(LayoutServices $layoutServices)
    {
        $this->layoutServices = $layoutServices;
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
     * Create a new layout with the provided data.
     *
     * @param Request $request The request instance containing layout data.
     * @return JsonResponse
     */
    public function createLayout(Request $request)
    {
        $layout = $this->layoutServices->createLayout($request->all());
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
