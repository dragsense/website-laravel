<?php

namespace DragSense\AutoCode\Models;

use DragSense\AutoCode\Http\Exceptions\ApiError;
use Illuminate\Database\Eloquent\Model;
use DragSense\AutoCode\Models\Traits\Paginatable;
use DragSense\AutoCode\Models\Traits\Duplicatable;
use Illuminate\Support\Str;

class Component extends Model
{
    use Paginatable, Duplicatable;

    // Define the table associated with the model
    protected $table = 'ac_components';

    protected $primaryKey = '_id'; // Specify the primary key
    public $incrementing = false; // Primary key is not auto-incrementing
    protected $keyType = 'string'; // Primary key type is string

    // Fillable attributes for mass assignment
    protected $fillable = [
        'name',
        'type',
        'parent',
        'elements',
        'components',
        'forms',
        'attached',
        '_collStates',
        'collStates',
        'styles',
        'states',
        'events',
        '_events',
        '_states',
        '_components',
        '_forms',
        '_elements',
        '_styles',
        'published',
        'updater',
        'duplicated',
        'creator',
    ];

    // Cast attributes to the desired data types
    protected $casts = [
        'elements' => 'json',
        'components' => 'array',
        'forms' => 'array',
        '_collStates' => 'array',
        'collStates' => 'array',
        'styles' => 'json',
        'states' => 'array',
        'events' => 'array',
        '_events' => 'array',
        '_states' => 'array',
        '_components' => 'array',
        '_forms' => 'array',
        '_elements' => 'json',
        '_styles' => 'json',
        'updater' => 'array',
        'creator' => 'array',
    ];

    public $timestamps = true; // Enable timestamps for created_at and updated_at

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($component) {
            // Generate a unique ID for new components if not duplicated
            if (!$component->exists && !$component->duplicated) {
                $component->setupNewComponent();
                $component->_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Set up the new component by assigning unique identifiers and styles.
     */
    protected function setupNewComponent()
    {
        $elements = $this->elements;

        $id = Str::random(7);
        $className = 'ac-elem-' . $id;

        // Ensure the key '0' exists before modifying it
        if (isset($elements[0])) {
            $elements[0]['className'] = $className;
            $elements[0]['selector'] = '.' . $className;
            $styleUid = Str::random(7);
            $elements[0]['styleUid'] = $styleUid;

            $this->elements = $elements; // Set the modified elements array back to the model

            $styles = [];
            $styles[$styleUid] = [
                '_uid' => $styleUid,
                'selectorText' => '.' . $className,
                'type' => 'div',
                'style' => []
            ];
            $this->styles = $styles; // Set the modified styles array back to the model
        }
    }

    /**
     * Define the relationship to the parent component.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parentComponent()
    {
        return $this->belongsTo(Component::class, 'parent');
    }

    /**
     * Define the relationship to the attached collection.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attachedCollection()
    {
        return $this->belongsTo(Collection::class, 'attached');
    }

    /**
     * Get the parent components associated with this component.
     *
     * @return array
     */
    public function getParentComponentsAttribute()
    {
        $parentComponent = $this->parentComponent()
            ->select('_id', 'name')
            ->first();
        return $parentComponent ? [$parentComponent] : [];
    }

    /**
     * Get the attached collections associated with this component.
     *
     * @return array
     */
    public function getCollectionsAttribute()
    {
        $attachedCollection = $this->attachedCollection()
            ->select('_id', 'name')
            ->first();
        return $attachedCollection ? [$attachedCollection] : [];
    }
}
