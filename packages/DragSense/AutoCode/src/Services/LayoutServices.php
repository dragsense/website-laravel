<?php

namespace DragSense\AutoCode\Services;

use DragSense\AutoCode\Models\Layout;
use DragSense\AutoCode\Models\Component;
use DragSense\AutoCode\Http\Exceptions\ApiError;

class LayoutServices
{
    /**
     * Create a layout
     *
     * @param array $layoutData
     * @return Layout
     * @throws ApiError
     */
    public function createLayout(array $layoutData, $withTemplate): Layout
    {
        $name = $layoutData['name'] ?? null;
        $existingLayout = Layout::where('name', $name)->first();

        if ($existingLayout) {
            throw new ApiError('Name already taken', 400);
        }

        $layoutData['withTemplate'] = $withTemplate;

        if (!isset($layoutData['elements'])) {
            $layoutData['elements'] = [
                "0" => [
                    "_uid" => "0",
                    "tagName" => "div",
                    "name" => 'Layout Root Element',
                    "type" => "layout",
                    "layout" => "root",
                    "nodeValue" => "",
                    "childNodes" => []
                ]
            ];
        }

        return Layout::create($layoutData);
    }

    /**
     * Update a layout
     *
     * @param string $id
     * @param array $layoutData
     * @return Layout
     * @throws ApiError
     */
    public function updateLayout(string $id, array $layoutData): Layout
    {
        $layout = Layout::find($id);
        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        $layout->update($layoutData);
        return $layout;
    }

     /**
     * Update layout elements
     *
     * @param string $id
     * @param array $layoutData
     * @return  Layout
     * @throws ApiError
     */
    public function updateElements(string $id, array $layoutData): Layout
    {
        $layout = Layout::find($id);
        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        $layout->published = false;
        $layout->_components = $layoutData['_components'];
        $layout->_elements = $layoutData['_elements'];
        $layout->_forms = $layoutData['_forms'];

        $layout->update();
        return $layout;
    }

    /**
     * Publish a layout
     *
     * @param string $id
     * @param array $layoutData
     * @return  Layout
     * @throws ApiError
     */
    public function publishLayout(string $id, array $layoutData): Layout
    {
        $layout = Layout::find($id);
        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        if ($layout->published) {
            throw new ApiError('Layout is already published', 400);
        }

        $layout->elements = $layout->_elements;
        $layout->components = $layout->_components;
        $layout->forms = $layout->_forms;
        $layout->styles = $layout->_styles;
        $layout->published = true;
        $layout->updater = $layoutData['updater'] ?? null;

        $layout->update($layoutData);
        return $layout;
    }

    /**
     * Restore a layout
     *
     * @param string $id
     * @param array $layoutData
     * @return  Layout
     * @throws ApiError
     */
    public function restoreLayout(string $id, array $layoutData): Layout
    {
        $layout = Layout::find($id);

        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        if ($layout->published) {
            throw new ApiError('There are no new draft changes to restore', 400);
        }

        $layout->_elements = $layout->elements;
        $layout->_components = $layout->components;
        $layout->_forms = $layout->forms;
        $layout->_styles = $layout->styles;
        $layout->updater = $layoutData['updater'] ?? null;
        $layout->published = true;

        $layout->update($layoutData);
        return $layout;
    }

    /**
     * Update a layout style
     *
     * @param string $id
     * @param array $layoutData
     * @return  Layout
     * @throws ApiError
     */
    public function updateStyle(string $id, array $layoutData): Layout
    {
        $layout = Layout::find($id);
        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

      

        $layout->published = false;
        $layout->_styles = $layoutData['_styles'];

        $layout->update();
        return $layout;
    }


    /**
     * Get layouts with pagination
     *
     * @param array $filter
     * @param array $options
     * @return mixed
     */
    public function getLayouts(array $filter = [], array $options = [])
    {
        $projection = [
            'name',
            'creator',
            'created_at',
            'updated_at',
            'updater',
            '_id',
        ];

        return Layout::paginate($filter, $options, $projection);
    }

    /**
     * Get a single layout
     *
     * @param string $id
     * @return Layout
     * @throws ApiError
     */
    public function getLayout(string $id): Layout
    {
        $layout = Layout::find($id)->select('name', 'id');
        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        return (object) array_merge($layout->toArray());
    }

    /**
     * Get a layout style
     *
     * @param string $id
     * @param $stream
     * @return  mixed
     * @throws ApiError
     */
    public function getStyle(string $id, callable $streamCallback)
    {
        $projection = ['_styles', 'styles', '_id', 'published'];

        $layout = Layout::find($id, $projection);

        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        $doc = ['_id' => $layout->_id];
        $doc['styles'] = $layout->published ? $layout->styles : $layout->_styles;

        // $styles = array_map(function($style) {
        //     return is_string($style) ? json_decode($style, true) : $style;
        // }, $doc['styles']);

        if (count($doc['styles']) > 0) {
            $chunkSize = 1024;
 
            $jsonData = json_encode(['elements' =>  $doc['styles'], '_id' => $doc['_id']]);
            $chunkStr = $jsonData . "\n";
            $streamCallback($chunkStr);
        }
        $streamCallback(null);

    }

    /**
     * Duplicate a layout
     *
     * @param string $id
     * @return  Layout
     */
    public function duplicateLayout(string $id): Layout
    {
        return Layout::duplicate($id, "Layout");
    }


    /**
     * Delete a layout
     *
     * @param string $id
     * @return bool
     * @throws ApiError
     */
    public function deleteLayout(string $id): bool
    {
        $layout = Layout::find($id);
        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        $layout->delete();
        return true;
    }
}
