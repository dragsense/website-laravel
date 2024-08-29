<?php

namespace DragSense\AutoCode\Models;

use DB;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use DragSense\AutoCode\Models\Traits\Documentable;
use Illuminate\Database\Eloquent\Model;
use DragSense\AutoCode\Models\Traits\Paginatable;
use DragSense\AutoCode\Models\Traits\Duplicatable;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;
use Illuminate\Support\Str;

class Document extends Model
{
    use Paginatable, Duplicatable, Documentable, HasJsonRelationships;

    // Use guarded to prevent mass assignment vulnerabilities
    protected $guarded = [];

    protected $primaryKey = '_id';
    public $incrementing = false; // Primary key is not auto-incrementing
    protected $keyType = 'string'; // Primary key type is string

    // Fillable attributes for mass assignment
    protected $fillable = [
        'name',
        'slug',
        'url',
        'published',
        'setting',
        'updater',
        'coll',
        'duplicated',
        'relationships',
        'creator',
        'states',
        '_states'
    ];

    // Cast attributes to the desired data types
    protected $casts = [
        'states' => 'array',
        'coll' => 'array',
        '_states' => 'array',
        'relationships' => 'json',
        'setting' => 'array',
        'updater' => 'array',
        'creator' => 'array',
    ];

    public $timestamps = true; // Enable timestamps for created_at and updated_at

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($document) {
            // Generate a unique ID for new documents
            if (!$document->exists && !$document->duplicated) {
                $document->_id = Str::uuid()->toString();
            }

            // Ensure 'slug' is present in the collection; throw an error if not
            if (empty($document->coll['slug'])) {
                throw new ApiError('Slug not found in the collection', 400);
            }

            // Validate and set the URL if 'slug' is dirty (changed)
            if ($document->isDirty('slug')) {
                if (static::checkSlug($document)) {
                    throw new ApiError('Slug already taken', 400);
                }

                // Construct URL using collection slug and document slug
                $coll = $document->coll;
                $document->url = '/' . $coll['slug'] . '/' . $document->slug;
            }
        });
    }

    /**
     * Check if the given slug already exists in the database.
     *
     * @param Document $document
     * @return bool
     */
    protected static function checkSlug($document)
    {
        $modelName = $document->slug;

        // Check if a document with the same slug already exists
        return static::where('slug', $modelName)->exists();
    }

    /**
     * Define the relationship to the Media model for the image.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function image()
    {
        return $this->belongsTo(Media::class, 'setting->image');
    }

    /**
     * Get the populated images associated with the document.
     *
     * @return array
     */
    public function getPopulatedImagesAttribute()
    {
        $image = $this->image()->select('_id', 'name', 'src')->first();
        return $image ? [$image] : [];
    }

    /**
     * Get the image from the setting attribute.
     *
     * @return Media|null
     */
    public function getSettingImageAttribute()
    {
        return $this->image()->select('_id', 'name', 'src')->first();
    }
}
