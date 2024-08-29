<?php

namespace DragSense\AutoCode\Models;

use DB;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Illuminate\Database\Eloquent\Model;
use DragSense\AutoCode\Models\Traits\Paginatable;
use DragSense\AutoCode\Models\Traits\Duplicatable;
use Log;
use Schema;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Str;

class Collection extends Model
{
    use Paginatable, Duplicatable, HasJsonRelationships;

    // Define the table associated with the model
    protected $table = 'ac_collections';
    
    // Specify the primary key and its properties
    protected $primaryKey = '_id';
    public $incrementing = false; // Primary key is not auto-incrementing
    protected $keyType = 'string'; // Primary key type is string

    // Fillable attributes for mass assignment
    protected $fillable = [
        'name',
        'slug',
        'layout',
        'scripts',
        'url',
        'elements',
        'components',
        'collections',
        '_components',
        '_collections',
        'relationships',
        '_elements',
        '_styles',
        'published',
        'styles',
        'setting',
        'updater',
        'duplicated',
        'creator',
        'states',
        '_states'
    ];

    // Cast attributes to the desired data types
    protected $casts = [
        'scripts' => 'array',
        'elements' => 'json',
        'relationships' => 'json',
        'styles' => 'json',
        'states' => 'array',
        '_states' => 'array',
        'components' => 'array',
        'collections' => 'array',
        '_components' => 'array',
        '_collections' => 'array',
        '_elements' => 'json',
        '_styles' => 'json',
        'setting' => 'array',
        'updater' => 'array',
        'creator' => 'array',
    ];

    public $timestamps = true; // Enable timestamps for created_at and updated_at

    // Boot method to handle model events
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($collection) {
            // Generate a unique ID and setup new page for new collections if not duplicated
            if (!$collection->exists && !$collection->duplicated) {
                $collection->setupNewPage();
                $collection->_id = Str::uuid()->toString();
            }

            // Check if the slug has been modified and is unique
            if ($collection->isDirty('slug')) {
                if (static::checkSlug($collection)) {
                    throw new ApiError('Slug already taken', 400);
                }

                // Rename the table if the old table exists
                $oldPluralSlug = Str::plural(strtolower($collection->getOriginal('slug')));
                $newPluralSlug = Str::plural(strtolower($collection->slug));

                $oldTableName = 'ac_' . $oldPluralSlug;
                $newTableName = 'ac_' . $newPluralSlug;

                if (Schema::hasTable($oldTableName)) {
                    DB::statement("ALTER TABLE `{$oldTableName}` RENAME TO `{$newTableName}`");
                }

                // Set the URL attribute based on the slug
                $collection->url = '/' . $collection->slug;
            }
        });
    }

    /**
     * Setup unique identifiers and styles for the new collection.
     */
    protected function setupNewPage()
    {
        $elements = $this->elements;

        $id = Str::random(7);
        $className = 'ac-elem-' . $id;
        $styleUid = Str::random(7);

        // Ensure the key '0' exists before modifying it
        if (isset($elements[0])) {
            $elements[0]['className'] = $className;
            $elements[0]['selector'] = '.' . $className;
            $elements[0]['styleUid'] = $styleUid;

            // Update elements and styles attributes
            $this->elements = $elements;
            $this->styles = [
                $styleUid => [
                    '_uid' => $styleUid,
                    'selectorText' => '.' . $className,
                    'type' => 'div',
                    'style' => []
                ]
            ];
        }
    }

    /**
     * Check if the slug is already taken by other collections, forms, or pages,
     * or if a table with the slug name exists in the database.
     *
     * @param Collection $collection
     * @return bool
     */
    protected static function checkSlug($collection)
    {
        $modelName = $collection->slug;

        // Check for existing records with the same slug
        $existingCollection = static::where('slug', $modelName)->first();
        $existingForm = DB::table('ac_forms')->where('slug', $modelName)->first();
        $existingPage = DB::table('ac_pages')->where('slug', $modelName)->first();

        // Check if a table with the slug name exists
        $isModelSingular = Schema::hasTable('ac_' . $modelName);
        $isModelPlural = Schema::hasTable(Str::plural('ac_' . $modelName));

        // Return true if any of the conditions are met
        return $existingCollection || $existingPage || $existingForm || $isModelSingular || $isModelPlural;
    }

    // Define relationships with other models

    /**
     * Get the layout associated with the collection.
     */
    public function layout()
    {
        return $this->belongsTo(Layout::class, 'layout');
    }

    /**
     * Get the image associated with the collection.
     */
    public function image()
    {
        return $this->belongsTo(Media::class, 'setting->image');
    }

    /**
     * Get the related collections from the JSON relationships.
     */
    public function relationships()
    {
        return $this->belongsToJson(Collection::class, 'relationships');
    }

    /**
     * Get the populated layout attribute.
     */
    public function getPopulatedLayoutsAttribute()
    {
        $populatedLayout = $this->layout()
            ->select('_id', 'name')
            ->first();
        return $populatedLayout ? [$populatedLayout] : [];
    }

    /**
     * Get the populated image attribute.
     */
    public function getPopulatedImagesAttribute()
    {
        $populatedImage = $this->image()
            ->select('_id', 'name', 'src')
            ->first();
        return $populatedImage ? [$populatedImage] : [];
    }

    /**
     * Get the populated relationships attribute.
     */
    public function getPopulatedRelationsAttribute()
    {
        $populatedRelationships = $this->relationships()
            ->select('_id', 'name', 'slug')
            ->get();
        return $populatedRelationships->isNotEmpty() ? $populatedRelationships : [];
    }

    /**
     * Get the relations attribute with additional details.
     */
    public function getRelationsAttribute()
    {
        $populatedRelations = $this->relationships()
            ->select('_id', 'name', 'slug', 'url', 'created_at', 'creator', 'states', 'setting')
            ->get();
        return $populatedRelations->isNotEmpty() ? $populatedRelations : [];
    }
}
