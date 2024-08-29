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
    public function createLayout(array $layoutData): Layout
    {
        $name = $layoutData['name'] ?? null;
        $existingLayout = Layout::where('name', $name)->first();

        if ($existingLayout) {
            throw new ApiError('Name already taken', 400);
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
            'topComponent',
            'bottomComponent',
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
        $layout = Layout::find($id)->select('name', 'id', 'topComponent', 'bottomComponent', 'components');
        if (!$layout) {
            throw new ApiError('Layout not found', 404);
        }

        $populatedComponents = [
           // Component::find($layout->components['top'])->select('name', 'id'),
           // Component::find($layout->components['bottom'])->select('name', 'id'),
        ];

        return (object) array_merge($layout->toArray(), ['populatedComponents' => $populatedComponents]);
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
