<?php

namespace DragSense\AutoCode\Http\Controllers;

use DragSense\AutoCode\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DragSense\AutoCode\Services\ComponentServices;
use DragSense\AutoCode\Services\SettingServices;
use Illuminate\Http\JsonResponse;

class ComponentController extends Controller
{
    protected $componentServices;
    protected $settingServices;

    public function __construct(ComponentServices $componentServices, SettingServices $settingServices)
    {
        $this->componentServices = $componentServices;
        $this->settingServices = $settingServices;
    }

    /**
     * Retrieve a component by its ID.
     *
     * @param string $id The ID of the component.
     * @return JsonResponse
     */
    public function getComponent($id): JsonResponse
    {
        $component = $this->componentServices->getComponent($id);
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";
        return response()->json(['component' => $component, 'host' => $fullhost]);
    }

    /**
     * Retrieve all paths related to components.
     *
     * @return JsonResponse
     */
    public function getPaths(): JsonResponse
    {
        $paths = $this->componentServices->getPaths();
        \Log::info($paths);

        return response()->json(['paths' => $paths]);
    }

    /**
     * Duplicate a component by its ID.
     *
     * @param string $id The ID of the component.
     * @return JsonResponse
     */
    public function duplicateComponent($id): JsonResponse
    {
        $component = $this->componentServices->duplicateComponent($id);
        return response()->json(['component' => $component]);
    }

    /**
     * Create a new component with the given data.
     *
     * @param Request $request The request containing component data.
     * @return JsonResponse
     */
    public function createComponent(Request $request): JsonResponse
    {
        $component = $this->componentServices->createComponent($request->all());
        return response()->json(['component' => $component], 201);
    }

    /**
     * Publish a component by its ID.
     *
     * @param string $id The ID of the component.
     * @param Request $request The request containing publication data.
     * @return JsonResponse
     */
    public function publishComponent($id, Request $request): JsonResponse
    {
        $this->componentServices->publishComponent($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Restore a deleted component by its ID.
     *
     * @param string $id The ID of the component.
     * @param Request $request The request containing restoration data.
     * @return JsonResponse
     */
    public function restoreComponent($id, Request $request): JsonResponse
    {
        $this->componentServices->restoreComponent($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update an existing component by its ID with the given data.
     *
     * @param string $id The ID of the component.
     * @param Request $request The request containing updated data.
     * @return JsonResponse
     */
    public function updateComponent($id, Request $request): JsonResponse
    {
        $component = $this->componentServices->updateComponent($id, $request->all());
        return response()->json(['component' => $component]);
    }

    /**
     * Update the style of a component by its ID.
     *
     * @param string $id The ID of the component.
     * @param Request $request The request containing style data.
     * @return JsonResponse
     */
    public function updateStyle($id, Request $request): JsonResponse
    {
        $this->componentServices->updateStyle($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update the elements of a component by its ID.
     *
     * @param string $id The ID of the component.
     * @param Request $request The request containing elements data.
     * @return JsonResponse
     */
    public function updateElements($id, Request $request): JsonResponse
    {
        $component = $this->componentServices->updateElements($id, $request->all());
        return response()->json(['component' => $component]);
    }

    /**
     * Delete a component by its ID.
     *
     * @param string $id The ID of the component.
     * @return JsonResponse
     */
    public function deleteComponent($id): JsonResponse
    {
        $this->componentServices->deleteComponent($id);
        return response()->json(null, 204);
    }

    /**
     * Stream the elements of a component.
     *
     * @param string $id The ID of the component.
     * @return StreamedResponse
     */
    public function getElements($id): StreamedResponse
    {
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return new StreamedResponse(function() use ($id, $fullhost) {
            $this->settingServices->getElements($id, 'component', $fullhost, function ($data) {
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
     * Stream the style information of a component.
     *
     * @param string $id The ID of the component.
     * @return StreamedResponse
     */
    public function getStyle($id): StreamedResponse
    {
        return new StreamedResponse(function () use ($id) {
            $this->componentServices->getStyle($id, function ($data) {
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
     * Retrieve the CSS for a component.
     *
     * @param string $id The ID of the component.
     * @return JsonResponse
     */
    public function getCss($id): JsonResponse
    {
        $css = $this->settingServices->getCss($id, 'component');
        return response()->json($css);
    }

    /**
     * Retrieve a list of components with optional filters and options.
     *
     * @param Request $request The request containing filter and option parameters.
     * @return JsonResponse
     */
    public function getComponents(Request $request): JsonResponse
    {
        $filter = $request->only(['name']);
        $options = $request->only(['sortBy', 'limit', 'page']);
        $components = $this->componentServices->getComponents($filter, $options);
        return response()->json(['components' => $components]);
    }
}
