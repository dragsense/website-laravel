<?php

namespace DragSense\AutoCode\Models\Traits;

trait Paginatable
{
    /**
     * Paginate results with filter, sorting, and population.
     *
     * @param array $filter
     * @param array $options
     * @param array|null $columns
     * @return array
     */
    public static function paginate(array $filter = [], array $options = [], array $columns = ['*'])
    {
        // Start a query on the model using self
        $query = self::query();

    

        foreach ($filter as $key => $value) {
            $query->where($key, 'LIKE', '%' . $value . '%');
        }

        // Sorting criteria
        if (isset($options['sortBy'])) {
            $sortingCriteria = explode(',', $options['sortBy']);

            $columnMapping = [
                'createdAt' => 'created_at',
            ];
        

            foreach ($sortingCriteria as $sortOption) {
                [$key, $order] = explode(':', $sortOption);

                if (array_key_exists($key, $columnMapping)) {
                    $key = $columnMapping[$key];
                }

                $query->orderBy($key, $order === 'desc' ? 'desc' : 'asc');
            }
        } else {
            $query->orderBy('created_at');
        }

        // Pagination
        $limit = isset($options['limit']) && (int) $options['limit'] > 0 ? (int) $options['limit'] : 10;
        $page = isset($options['page']) && (int) $options['page'] > 0 ? (int) $options['page'] : 1;
        $skip = ($page - 1) * $limit;

        // Count total results
        $totalResults = $query->count();

        // Retrieve results
        $results = $query->skip($skip)->limit($limit)->get($columns);

        // Calculate total pages
        $totalPages = (int) ceil($totalResults / $limit);

        return [
            'results' => $results,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
            'totalResults' => $totalResults,
        ];
    }
}
