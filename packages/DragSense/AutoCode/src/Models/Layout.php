<?php

namespace DragSense\AutoCode\Models;

use Illuminate\Database\Eloquent\Model;
use DragSense\AutoCode\Models\Traits\Paginatable;
use Str;

class Layout extends Model{

    use Paginatable;

    // Define the table associated with the model
    protected $table = 'ac_layouts'; 

    protected $primaryKey = '_id'; 
    public $incrementing = false;
    protected $keyType = 'string';
    
    protected $fillable = [
        'name',
        'topComponent',
        'bottomComponent',
        'components',
        'updater',
        'creator',
    ];

    // Cast attributes to their appropriate types
    
    protected $casts = [
        'topComponent' => 'boolean',
        'bottomComponent' => 'boolean',
        'components' => 'array', 
        'creator' => 'array',
    ];

    // Set default values for attributes
    protected $attributes = [
        'topComponent' => false,
        'bottomComponent' => false,
        'components' => '[]',
        'updater' => '[]',
        'creator' => '[]',
    ];


    protected static function boot()
    {
        parent::boot();

        static::saving(function ($layout) {
        
            
            if (!$layout->exists && !$layout->duplicated) {

                $uniqueId = Str::uuid()->toString();
                $layout->_id = $uniqueId;
            }
           
        });
    }

}
