<?php

namespace DragSense\AutoCode\Models\Traits;

use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\ApiError;

trait Documentable
{
    /**
     * Static method to filter documents based on various criteria.
     *
     * @param array $docIds
     * @param array $filters
     * @param array $relationships
     * @param array $collections
     * @param array $options
     * @return array
     * @throws ApiError
     */
    public static function filterDocuments(
        array $docIds,
        array $filters,
        array $relationships = [],
        array $options = [],
        callable $getDocumentModel,
    ) {
        try {
            // Create the base query
            $query = self::whereIn('_id', $docIds);

            // Apply filter criteria
            foreach ($filters as $filter) {
                if (!empty($filter['defaultValue'])) {
                    if ($filter['filterKey'][0] === 'STATES') {
                        $query->where("states->" . $filter['filterKey'][1] . "->defaultValue", 'like', '%' . $filter['defaultValue'] . '%');
                    } elseif ($filter['filterKey'][0] === 'RELATIONSHIPS') {
                        $query->whereIn("relationships->" . $filter['filterKey'][1], (array) $filter['defaultValue']);
                    } elseif ($filter['filterKey'][0] === '_id') {
                        $query->where('id', $filter['defaultValue']);
                    } else {
                        $query->where($filter['filterKey'][0], 'like', '%' . $filter['defaultValue'] . '%');
                    }
                }
            }


            // Sorting
            if (!empty($options['sortBy'])) {
                $sortOptions = explode(',', $options['sortBy']);

                $columnMapping = [
                    'createdAt' => 'created_at',
                ];

                foreach ($sortOptions as $sortOption) {
                    list($key, $order) = explode(':', $sortOption);

                    if (array_key_exists($key, $columnMapping)) {
                        $key = $columnMapping[$key];
                    }

                    $query->orderBy($key, $order === 'desc' ? 'desc' : 'asc');
                }
            } else {
                $query->orderBy('created_at', 'asc');
            }

            // Pagination
            $limit = isset($options['limit']) && intval($options['limit']) > 0 ? intval($options['limit']) : 10;
            $page = isset($options['page']) && intval($options['page']) > 0 ? intval($options['page']) : 1;
            $skip = ($page - 1) * $limit;

            // Execute the query
            $documents = $query->skip($skip)->take($limit)->get();


            foreach ($relationships as $relationship) {
                if (isset($relationship['slug']) && isset($relationship['_id'])) {
                    $relatedSlug = $relationship['slug'];
                    $relatedId = $relationship['_id'];

                    $documentModel = $getDocumentModel($relatedSlug);

                    $docIds = $documents->pluck("relationships.$relatedId")->flatten()->filter()->unique();

                    $relatedDocuments = $documentModel::whereIn('_id', $docIds)
                        ->get(['name', 'url', 'created_at', 'creator', 'slug', '_id', 'states', 'setting']);
    
                    $relatedDocsById = $relatedDocuments->keyBy('_id');

                    foreach ($documents as &$document) {
                        if (isset($document->relationships[$relatedId])) {
                            $relatedDocIds = (array)$document->relationships[$relatedId];
                            $populatedRelationships[$relatedId] = $relatedDocsById->only($relatedDocIds)->values();
    

                                foreach ($relatedDocuments as &$relatedDocument) {
                                    $populatedImage = $relatedDocument->settingImage;
                                    $setting = $relatedDocument['setting'];
                                    $setting['populatedImage'] = $populatedImage;
                                    $relatedDocument['setting'] =  $setting;
                                }

                            $document->populatedRelationships = $populatedRelationships;
                        }

                        $populatedImage = $document->settingImage;
                        $setting = $document['setting'];
                        $setting['populatedImage'] = $populatedImage;
                        $document['setting'] =  $setting;

                    }
                }
            }




            return [
                'documents' => $documents,
                'total' => $documents->count(),
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return [
                'documents' => [],
                'total' => 0,
            ];
        }
    }

    /**
     * Static method to filter all documents with additional criteria.
     *
     * @param array $filters
     * @param array $relationships
     * @param array $filter
     * @param array $options
     * @param array $collections
     * @return array
     * @throws ApiError
     */
    public static function filterAllDocuments(
        array $filters,
        array $relationships = [],
        array $filter = [],
        array $options = [],
        callable $getDocumentModel,
    ) {
        try {

            // Create the base query
            $query = self::query();



            foreach ($filter as $key => $value) {
                $query->where($key, 'LIKE', '%' . $value . '%');
            }

            // Apply filter criteria
            foreach ($filters as $filterItem) {
                if (!empty($filterItem['defaultValue'])) {
                    if ($filterItem['filterKey'][0] === 'STATES') {
                        $query->where("states->" . $filterItem['filterKey'][1] . "->defaultValue", 'like', '%' . $filterItem['defaultValue'] . '%');
                    } elseif ($filterItem['filterKey'][0] === 'RELATIONSHIPS') {
                        $query->whereIn("relationships->" . $filterItem['filterKey'][1], (array) $filterItem['defaultValue']);
                    } elseif ($filterItem['filterKey'][0] === '_id') {
                        $query->where('id', $filterItem['defaultValue']);
                    } else {
                        $query->where($filterItem['filterKey'][0], 'like', '%' . $filterItem['defaultValue'] . '%');
                    }
                }
            }


            // Sorting
            if (!empty($options['sortBy'])) {
                $sortOptions = explode(',', $options['sortBy']);

                $columnMapping = [
                    'createdAt' => 'created_at',
                ];

                foreach ($sortOptions as $sortOption) {
                    list($key, $order) = explode(':', $sortOption);

                    if (array_key_exists($key, $columnMapping)) {
                        $key = $columnMapping[$key];
                    }

                    $query->orderBy($key, $order === 'desc' ? 'desc' : 'asc');
                }
            } else {
                $query->orderBy('created_at', 'asc');
            }

            // Pagination
            $limit = isset($options['limit']) && intval($options['limit']) > 0 ? intval($options['limit']) : 10;
            $page = isset($options['page']) && intval($options['page']) > 0 ? intval($options['page']) : 1;
            $skip = ($page - 1) * $limit;

            // Execute the query
            $documents = $query->skip($skip)->take($limit)->get();

            foreach ($relationships as $relationship) {
                if (isset($relationship['slug']) && isset($relationship['_id'])) {
                    $relatedSlug = $relationship['slug'];
                    $relatedId = $relationship['_id'];

                    $documentModel = $getDocumentModel($relatedSlug);

                    $docIds = $documents->pluck("relationships.$relatedId")->flatten()->filter()->unique();

                    $relatedDocuments = $documentModel::whereIn('_id', $docIds)
                        ->get(['name', 'url', 'created_at', 'creator', 'slug', '_id', 'states', 'setting']);
    
                    $relatedDocsById = $relatedDocuments->keyBy('_id');

                    foreach ($documents as &$document) {
                        if (isset($document->relationships[$relatedId])) {
                            $relatedDocIds = (array)$document->relationships[$relatedId];
                            $populatedRelationships[$relatedId] = $relatedDocsById->only($relatedDocIds)->values();
    

                                foreach ($relatedDocuments as &$relatedDocument) {
                                    $populatedImage = $relatedDocument->settingImage;
                                    $setting = $relatedDocument['setting'];
                                    $setting['populatedImage'] = $populatedImage;
                                    $relatedDocument['setting'] =  $setting;
                                }

                            $document->populatedRelationships = $populatedRelationships;
                        }

                        $populatedImage = $document->settingImage;
                        $setting = $document['setting'];
                        $setting['populatedImage'] = $populatedImage;
                        $document['setting'] =  $setting;

                    }
                }
            }


            return [
                'documents' => $documents,
                'total' => $documents->count(),
            ];
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return [
                'documents' => [],
                'total' => 0,
            ];
        }
    }
}