<?php

namespace DragSense\AutoCode\Services;

use DB;
use DragSense\AutoCode\Models\Media;
use DragSense\AutoCode\Models\Collection;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Exception;
use Illuminate\Support\Facades\Log;
use Str;

class CollectionServices
{
    /**
     * Create a collection
     *
     * @param array $collectionData
     * @return  Collection
     */
    public function createCollection(array $collectionData): Collection
    {
        if (isset($collectionData['setting']['content'])) {
            $collectionData['setting']['content'] = html_entity_decode($collectionData['setting']['content']);
        }

        if (isset($collectionData['scripts'])) {
            $collectionData['scripts']['head'] = html_entity_decode($collectionData['scripts']['head'] ?? '');
            $collectionData['scripts']['footer'] = html_entity_decode($collectionData['scripts']['footer'] ?? '');
        }

        if (!isset($collectionData['elements'])) {
            $collectionData['elements'] = [
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

        return Collection::create($collectionData);
    }

    /**
     * Update a collection
     *
     * @param string $id
     * @param array $collectionData
     * @return  Collection
     * @throws ApiError
     */
    public function updateCollection(string $id, array $collectionData): Collection
    {
        $collection = Collection::find($id);
        if (!$collection) {
            throw new ApiError('Collection not found', 404);
        }

        if (isset($collectionData['setting']['content'])) {
            $collectionData['setting']['content'] = html_entity_decode($collectionData['setting']['content']);
        }

        if (isset($collectionData['scripts'])) {
            $collectionData['scripts']['head'] = html_entity_decode($collectionData['scripts']['head'] ?? '');
            $collectionData['scripts']['footer'] = html_entity_decode($collectionData['scripts']['footer'] ?? '');
        }

        $collection->update($collectionData);
        return $collection;
    }

    /**
     * Update collection elements
     *
     * @param string $id
     * @param array $collectionData
     * @return  Collection
     * @throws ApiError
     */
    public function updateElements(string $id, array $collectionData): Collection
    {
        $collection = Collection::find($id);
        if (!$collection) {
            throw new ApiError('Collection not found', 404);
        }

        $collection->published = false;
        $collection->_components = $collectionData['_components'];
        $collection->_elements = $collectionData['_elements'];
        $collection->_forms = $collectionData['_forms'];

        $collection->update();
        return $collection;
    }

    /**
     * Publish a collection
     *
     * @param string $id
     * @param array $collectionData
     * @return  Collection
     * @throws ApiError
     */
    public function publishCollection(string $id, array $collectionData): Collection
    {
        $collection = Collection::find($id);
        if (!$collection) {
            throw new ApiError('Collection not found', 404);
        }

        if ($collection->published) {
            throw new ApiError('Collection is already published', 400);
        }

        $collection->elements = $collection->_elements;
        $collection->components = $collection->_components;
        $collection->forms = $collection->_forms;
        $collection->styles = $collection->_styles;
        $collection->published = true;
        $collection->updater = $collectionData['updater'] ?? null;

        $collection->update();
        return $collection;
    }

    /**
     * Restore a collection
     *
     * @param string $id
     * @param array $collectionData
     * @return  Collection
     * @throws ApiError
     */
    public function restoreCollection(string $id, array $collectionData): Collection
    {
        $collection = Collection::find($id);

        if (!$collection) {
            throw new ApiError('Collection not found', 404);
        }

        if ($collection->published) {
            throw new ApiError('There are no new draft changes to restore', 400);
        }

        $collection->_elements = $collection->elements;
        $collection->_components = $collection->components;
        $collection->_forms = $collection->forms;
        $collection->_styles = $collection->styles;
        $collection->updater = $collectionData['updater'] ?? null;
        $collection->published = true;

        $collection->update($collectionData);
        return $collection;
    }

    /**
     * Update a collection style
     *
     * @param string $id
     * @param array $collectionData
     * @return  Collection
     * @throws ApiError
     */
    public function updateStyle(string $id, array $collectionData): Collection
    {
        $collection = Collection::find($id);
        if (!$collection) {
            throw new ApiError('Collection not found', 404);
        }



        $collection->published = false;
        $collection->_styles = $collectionData['_styles'];

        $collection->update();
        return $collection;
    }

    /**
     * Get collections with pagination
     *
     * @param array $filter
     * @param array $options
     * @return  mixed
     */
    public function getCollections(array $filter = [], array $options = [])
    {
        $projection = [
            'name',
            'slug',
            'setting',
            'creator',
            'created_at',
            'updated_at',
            'updater',
            '_id',
        ];

        return Collection::paginate($filter, $options, $projection);
    }

    /**
     * Get a single collection
     *
     * @param string $id
     * @return  Collection
     * @throws ApiError
     */
    public function getCollection(string $id): Collection
    {


        $projection = [
            'name',
            'scripts',
            'slug',
            'type',
            '_id',
            'layout',
            'setting',
            'relationships',
            'states',
        ];

        $collection = Collection::select($projection)->find($id);
        if (!$collection) {
            throw new ApiError('Collection not found', 404);
        }


        $collection->populatedLayout = $collection->populatedLayouts;
        $collection->populatedImage = $collection->populatedImages;

        $collection->populatedRelationships = $collection->populatedRelations;


        return $collection;
    }

    /**
     * Get a collection style
     *
     * @param string $id
     * @param $stream
     * @return  mixed
     * @throws ApiError
     */
    public function getStyle(string $id, callable $streamCallback)
    {
        $projection = ['_styles', 'styles', '_id', 'published'];

        $collection = Collection::find($id, $projection);

        if (!$collection) {
            throw new ApiError('Collection not found', 404);
        }

        $doc = ['_id' => $collection->_id];
        $doc['styles'] = $collection->published ? $collection->styles : $collection->_styles;

        // $styles = array_map(function($style) {
        //     return is_string($style) ? json_decode($style, true) : $style;
        // }, $doc['styles']);

        if (count($doc['styles']) > 0) {
            $chunkSize = 1024;

            $jsonData = json_encode(['elements' => $doc['styles'], '_id' => $doc['_id']]);
            $chunkStr = $jsonData . "\n";
            $streamCallback($chunkStr);
        }
        $streamCallback(null);

    }

    /**
     * Duplicate a collection
     *
     * @param string $id
     * @return  Collection
     */
    public function duplicateCollection(string $id): Collection
    {
        return Collection::duplicate($id, "Collection");
    }



    /**
     * Delete a collection
     *
     * @param string $id
     * @return bool
     * @throws ApiError
     */
    public function deleteCollection(string $id): bool
    {
        try {
            // Find the collection by ID
            $collection = Collection::find($id);

            if (!$collection) {
                throw new ApiError('Collection not found', 404);
            }

            $pluralSlug = Str::plural(strtolower($collection->slug));

            $tableName = 'ac_' . $pluralSlug;

            Log::info($tableName);
            // Delete the table associated with the collection
            $deleted = DB::statement("DROP TABLE IF EXISTS `{$tableName}`");

            if (!$deleted) {
                throw new ApiError('Table not found', 404);
            }

            // Delete the collection object from the database
            $collection->delete();

            return true;
        } catch (Exception $e) {
            // Log the error and throw a generic API error
            error_log('An unexpected error occurred: ' . $e->getMessage());
            throw new ApiError('An unexpected error occurred', 500);
        }
    }


}
