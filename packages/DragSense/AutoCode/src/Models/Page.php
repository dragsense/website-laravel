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
use Illuminate\Support\Str;

class Page extends Model
{
    use Paginatable, Duplicatable, HasJsonRelationships;

    protected $table = 'ac_pages';
    protected $primaryKey = '_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'slug',
        'layout',
        'scripts',
        'url',
        'elements',
        'components',
        'forms',
        '_components',
        '_forms',
        '_elements',
        '_styles',
        'published',
        'styles',
        'setting',
        'updater',
        'duplicated',
        'creator',
    ];

    protected $casts = [
        'scripts' => 'array',
        'elements' => 'json',
        'styles' => 'json',
        'components' => 'array',
        'forms' => 'array',
        '_components' => 'array',
        '_forms' => 'array',
        '_elements' => 'json',
        '_styles' => 'json',
        'setting' => 'array',
        'updater' => 'array',
        'creator' => 'array',
    ];

    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($page) {
            if (!$page->exists && !$page->duplicated) {
                $page->setupNewPage();
                $page->_id = Str::uuid()->toString();
            }

            if ($page->isDirty('slug')) {
                if (static::checkSlug($page)) {
                    throw new ApiError('Slug already taken', 400);
                }
                $page->url = '/' . $page->slug;
            }
        });
    }

    /**
     * Setup a new page with default elements and styles.
     *
     * @return void
     */
    protected function setupNewPage()
    {
        $elements = $this->elements;

        if (isset($elements[0])) {
            $id = Str::random(7);
            $className = 'ac-elem-' . $id;
            $styleUid = Str::random(7);

            $elements[0]['className'] = $className;
            $elements[0]['selector'] = '.' . $className;
            $elements[0]['styleUid'] = $styleUid;

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
     * Check if a slug is already in use in the relevant tables.
     *
     * @param \DragSense\AutoCode\Models\Page $page
     * @return bool
     */
    protected static function checkSlug($page)
    {
        $modelName = $page->slug;

        $existsInPages = static::where('slug', $modelName)->exists();
        $existsInCollections = DB::table('ac_collections')->where('slug', $modelName)->exists();
        $existsInForms = DB::table('ac_forms')->where('slug', $modelName)->exists();
        $existsInSingularTable = Schema::hasTable('ac_' . $modelName);
        $existsInPluralTable = Schema::hasTable(Str::plural('ac_' . $modelName));

        return $existsInPages || $existsInCollections || $existsInForms || $existsInSingularTable || $existsInPluralTable;
    }

    /**
     * Get the layout associated with the page.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function layout()
    {
        return $this->belongsTo(Layout::class, 'layout');
    }

    /**
     * Get the image associated with the page settings.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function image()
    {
        return $this->belongsTo(Media::class, 'setting->image');
    }

    /**
     * Get populated layouts attribute.
     *
     * @return array
     */
    public function getPopulatedLayoutsAttribute()
    {
        $populatedLayout = $this->layout()->select('_id', 'name')->first();
        return $populatedLayout ? [$populatedLayout] : [];
    }

    /**
     * Get populated images attribute.
     *
     * @return array
     */
    public function getPopulatedImagesAttribute()
    {
        $populatedImage = $this->image()->select('_id', 'name', 'src')->first();
        return $populatedImage ? [$populatedImage] : [];
    }
}
