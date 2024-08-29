<?php

namespace DragSense\AutoCode\Models;

use Illuminate\Database\Eloquent\Model;
use DragSense\AutoCode\Models\Traits\Paginatable;
use Illuminate\Support\Str;

class Media extends Model
{
    use Paginatable;

    protected $table = 'ac_media';

    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'src',
        'name',
        'fileName',
        'type',
        'alt',
        'size',
        'dimensions',
        'mimetype',
    ];

    protected $casts = [
        'dimensions' => 'array',
    ];

    protected $attributes = [
        'alt' => '',
        'size' => 0,
        'mimetype' => '',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($media) {
            if (!$media->exists && !$media->duplicated) {
                $media->_id = Str::uuid()->toString();
            }
        });
    }
}
