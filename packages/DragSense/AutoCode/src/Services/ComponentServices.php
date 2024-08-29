<?php

namespace DragSense\AutoCode\Services;

use DragSense\AutoCode\Models\Component;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ComponentServices
{
    /**
     * Create a component
     *
     * @param array $componentData
     * @return Component
     */
    public function createComponent(array $componentData): Component
    {

        
        if (!isset($componentData['elements'])) {
            $componentData['elements'] = [
                "0" => [
                    "_uid" => "0",
                    "tagName" => "div",
                    "type" => "layout",
                    "layout" => "root",
                    "nodeValue" => "",
                    "childNodes" => []
                ]
            ];
        }

        return Component::create($componentData);
    }

    /**
     * Update a component
     *
     * @param string $id
     * @param array $componentData
     * @return Component
     * @throws ApiError
     */
    public function updateComponent(string $id, array $componentData): Component
    {
        $component = Component::find($id);
        if (!$component) {
            throw new ApiError('Component not found', 404);
        }

        if (isset($componentData['states'])) {
            $componentData['_states'] = $componentData['states'];
        }


        $component->update($componentData);
        return $component;
    }

    /**
     * Update a component's elements
     *
     * @param string $id
     * @param array $componentData
     * @return Component
     * @throws ApiError
     */
    public function updateElements(string $id, array $componentData): Component
    {
        $component = Component::find($id);
        if (!$component) {
            throw new ApiError('Component not found', 404);
        }

        $component->published = false;
        $component->_components = $componentData['_components'];
        $component->_elements = $componentData['_elements'];
        $component->_forms = $componentData['_forms'];
        $component->_states = $componentData['_states'];
        $component->_events = $componentData['_events'];
        $component->_collStates = $componentData['_collStates'];


        $component->update();
        return $component;
    }

    /**
     * Publish a component
     *
     * @param string $id
     * @param array $componentData
     * @return Component
     * @throws ApiError
     */
    public function publishComponent(string $id, array $componentData): Component
    {
        $component = Component::find($id);
        if (!$component) {
            throw new ApiError('Component not found', 404);
        }

        if ($component->published) {
            throw new ApiError('Component is already published', 400);
        }

        $component->elements = $component->_elements;
        $component->components = $component->_components;
        $component->forms = $component->_forms;
        $component->styles = $component->_styles;
        $component->states = $component->_states;
        $component->events = $component->_events;
        $component->collStates = $component->_collStates;

        $component->published = true;
        $component->updater = $componentData['updater'] ?? null;

        $component->update();
        return $component;
    }

    /**
     * Restore a component
     *
     * @param string $id
     * @param array $componentData
     * @return Component
     * @throws ApiError
     */
    public function restoreComponent(string $id, array $componentData): Component
    {
        $component = Component::find($id);
        if (!$component) {
            throw new ApiError('Component not found', 404);
        }

        if ($component->published) {
            throw new ApiError('There are no new draft changes to restore', 400);
        }

        $component->_elements = $component->elements;
        $component->_components = $component->components;
        $component->_forms = $component->forms;
        $component->_styles = $component->styles;
        $component->_states = $component->states;
        $component->_events = $component->events;
        $component->_collStates = $component->collStates;

        $component->updater = $componentData['updater'] ?? null;
        $component->published = true;

        $component->update();
        return $component;
    }

    /**
     * Get custom paths
     *
     * @return array
     */
    public function getPaths(): array
    {
        $componentsDir = resource_path('js/AutoCode/components');
        $folders = array_filter(glob($componentsDir . '/*'), 'is_dir');
        return array_map('basename', $folders);
    }

    /**
     * Get components with pagination
     *
     * @param array $filter
     * @param array $options
     * @return mixed
     */
    public function getComponents(array $filter = [], array $options = [])
    {
        $projection = [
            'name',
            'parent',
            'attached',
            'creator',
            'created_at',
            'updated_at',
            'updater',
            '_id',
        ];

        return Component::paginate($filter, $options, $projection);
    }

    /**
     * Duplicate a component
     *
     * @param string $id
     * @return Component
     */
    public function duplicateComponent(string $id): Component
    {
        // Assuming Component model has a duplicate method
        return Component::duplicate($id, "Component");
    }

    /**
     * Get a component
     *
     * @param string $id
     * @return Component
     * @throws ApiError
     */
    public function getComponent(string $id): Component
    {

        $projection = [
            'name',
            'type',
            '_id',
            'parent',
            'attached',
            'states',
        ];

        $component = Component::select($projection)->find($id);

        if (!$component) {
            throw new ApiError('Component not found', 404);
        }
        $component->component = $component->parentComponents;
        $component->collection = $component->collections;

        return $component;
    }

    /**
     * Get component style
     *
     * @param string $id
     * @param $stream
     * @return void
     * @throws ApiError
     */
    public function getStyle(string $id, callable $streamCallback): void
    {

        $projection = ['_styles', 'styles', '_id', 'published'];

        $component = Component::find($id, $projection);
        if (!$component) {
            throw new ApiError('Component not found', 404);
        }

        $doc = ['_id' => $component->_id];
        $doc['styles'] = $component->published ? $component->styles : $component->_styles;

        if (count($doc['styles']) > 0) {
            $chunkSize = 1024;

            $jsonData = json_encode(['elements' => $doc['styles'], '_id' => $doc['_id']]);
            
            $chunkStr = $jsonData . "\n";
            $streamCallback($chunkStr);
        }
        $streamCallback(null);
    }



    /**
     * Update a page style
     *
     * @param string $id
     * @param array $pageData
     * @return  Component
     * @throws ApiError
     */
    public function updateStyle(string $id, array $componentData): Component
    {
        $component = Component::find($id);
        if (!$component) {
            throw new ApiError('Page not found', 404);
        }

        $component->published = false;
        $component->_styles = $componentData['_styles'];

        $component->update();
        return $component;
    }


    /**
     * Delete a component
     *
     * @param string $id
     * @return bool
     * @throws ApiError
     */
    public function deleteComponent(string $id): bool
    {
        $component = Component::find($id);
        if (!$component) {
            throw new ApiError('Component not found', 404);
        }

        $component->delete();
        return true;
    }
}
