<?php

namespace DragSense\AutoCode\Models;

use DB;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Illuminate\Database\Eloquent\Model;
use DragSense\AutoCode\Models\Traits\Paginatable;
use DragSense\AutoCode\Models\Traits\Duplicatable;
use Illuminate\Support\Str;
use Schema;
use Staudenmeir\EloquentJsonRelations\HasJsonRelationships;

class Form extends Model
{
    use Paginatable, Duplicatable, HasJsonRelationships;

    // Define the table associated with the model
    protected $table = 'ac_forms';
    
    // Specify the primary key and its properties
    protected $primaryKey = '_id';
    public $incrementing = false; // Primary key is not auto-incrementing
    protected $keyType = 'string'; // Primary key type is string

    // Fillable attributes for mass assignment
    protected $fillable = [
        'name',
        'slug',
        'type',
        'record',
        'recordOnly',
        'emailbody',
        'elements',
        'components',
        '_components',
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
        'emailbody' => 'json',
        'elements' => 'json',
        'styles' => 'json',
        'states' => 'array',
        '_states' => 'array',
        'components' => 'array',
        '_components' => 'array',
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

        static::saving(function ($form) {
            // Generate a unique ID and identifiers for new forms if not duplicated
            if (!$form->exists && !$form->duplicated) {
                $form->generateUniqueIdentifiersAndStyles();
                $form->_id = Str::uuid()->toString();
            }

            // Check if the slug has been modified and is unique
            if ($form->isDirty('slug')) {
                if (static::checkSlug($form)) {
                    throw new ApiError('Slug already taken', 400);
                }
            }
        });
    }

    /**
     * Generate unique identifiers and styles for the form elements.
     */
    protected function generateUniqueIdentifiersAndStyles()
    {
        $elements = $this->elements;

        if (isset($elements['0'])) {
            // Generate unique identifiers
            $id = Str::random(7);
            $className = 'ac-elem-' . $id;
            $styleUid = Str::random(7);

            // Assign unique identifiers to the first element
            $elements['0']['className'] = $className;
            $elements['0']['selector'] = '.' . $className;
            $elements['0']['styleUid'] = $styleUid;

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
     * Check if the slug is already taken by other forms, collections, or pages,
     * or if a table with the slug name exists in the database.
     *
     * @param Form $form
     * @return bool
     */
    protected static function checkSlug($form)
    {
        $modelName = $form->slug;

        // Check for existing records with the same slug
        $existingForm = static::where('slug', $modelName)->first();
        $existingCollection = DB::table('ac_collections')->where('slug', $modelName)->first();
        $existingPage = DB::table('ac_pages')->where('slug', $modelName)->first();

        // Check if a table with the slug name exists
        $isModelSingular = Schema::hasTable('ac_' . $modelName);
        $isModelPlural = Schema::hasTable(Str::plural('ac_' . $modelName));

        // Return true if any of the conditions are met
        return $existingCollection || $existingPage || $existingForm || $isModelSingular || $isModelPlural;
    }
}
