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
       'main',
        'elements',
        'components',
        'forms',
        '_components',
        '_forms',
        '_elements',
        '_styles',
        'published',
        'styles',
        'updater',
        'withTemplate',
        'duplicated',
        'creator',
    ];

    // Cast attributes to their appropriate types
    
    protected $casts = [
        'elements' => 'json',
        'styles' => 'json',
        'components' => 'array',
        'forms' => 'array',
        '_components' => 'array',
        '_forms' => 'array',
        '_elements' => 'json',
        '_styles' => 'json',
        'updater' => 'array',
        'creator' => 'array',
    ];

    public $timestamps = true;


    protected static function boot()
    {
        parent::boot();

        static::saving(function ($layout) {
        
    
            if (!$layout->exists && !$layout->duplicated) {
                $layout->setupNewLayout();

                $uniqueId = Str::uuid()->toString();
                $layout->_id = $uniqueId;
            }
           
        });
    }


      /**
     * Setup a new layout with unique identifiers and styles.
     *
     * @return void
     */
    protected function setupNewLayout()
    {
        $elements = $this->elements;

        $id = Str::random(7);
        $className = 'ac-elem-root-' . $id;
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

        if (isset($this->withTemplate) && $this->withTemplate) {
            $this->setupTemplate($styleUid, $this->withTemplate);
        } else {
            $this->main = '0';
        }

        unset($this->withTemplate);
    }

     /**
     * Sets up the layout with child elements and grid layout if using a template.
     *
     * @param string $styleUid The root element's unique style UID.
     * @param string $withTemplate The template option.
     * @return void
     */
    private function setupTemplate($styleUid, $withTemplate)
    {
        $elements = $this->elements;
        $styles = $this->styles;

        $includeLeft = $withTemplate !== 'noLeft' && $withTemplate !== 'noSides';
        $includeRight = $withTemplate !== 'noRight' && $withTemplate !== 'noSides';

        $elements[0]['childNodes'] = ['1']; // Always include Header
        $nextElementId = 2;

        if ($includeLeft) {
            $elements[0]['childNodes'][] = (string) $nextElementId; // Add Left Aside
            $nextElementId++;
        }

        $elements[0]['childNodes'][] = (string) $nextElementId; // Add Main
        $mainElementId = $nextElementId;
        $nextElementId++;

        if ($includeRight) {
            $elements[0]['childNodes'][] = (string) $nextElementId; // Add Right Aside
            $nextElementId++;
        }

        $elements[0]['childNodes'][] = (string) $nextElementId; // Add Footer

        $childClassNames = [
            1 => 'header',
            $mainElementId => 'main',
            $nextElementId => 'footer',
        ];

        $tagNames = [
            1 => 'header',
            $mainElementId => 'main',
            $nextElementId => 'footer',
        ];

        $names = [
            1 => 'Header',
            $mainElementId => 'Main',
            $nextElementId => 'Footer',
        ];

        if ($includeLeft) {
            $childClassNames[2] = 'left-aside';
            $tagNames[2] = 'aside';
            $names[2] = 'Left Aside';
        }

        if ($includeRight) {
            $childClassNames[$nextElementId - 1] = 'right-aside';
            $tagNames[$nextElementId - 1] = 'aside';
            $names[$nextElementId - 1] = 'Right Aside';
        }

        foreach ($elements[0]['childNodes'] as $i) {
            $id = Str::random(7);
            $childClassName = 'ac-element-' . $childClassNames[$i] . '-' . $id;

            $elements[$i] = [
                '_uid' => $i,
                'tagName' => $tagNames[$i],
                'type' => 'layout',
                'className' => $childClassName,
                'selector' => '.' . $childClassName,
                'childNodes' => [],
                'styleUid' => Str::random(7),
                'name' => $names[$i],
            ];

            $styles[$elements[$i]['styleUid']] = [
                '_uid' => $elements[$i]['styleUid'],
                'selectorText' => '.' . $childClassName,
                'type' => $tagNames[$i],
                'style' => [
                    'normal' => [
                        'root' => [
                            'properties' => [
                                'grid-area' => [
                                    'value' => str_replace('-', ' ', $childClassNames[$i]),
                                    'unit' => '',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $gridTemplateAreas = "
            \"header header header\"
            \"" . ($includeLeft ? 'left-aside' : '') . " main " . ($includeRight ? 'right-aside' : '') . "\"
            \"footer footer footer\"
        ";

        $styles[$styleUid]['style'] = [
            'normal' => [
                'root' => [
                    'properties' => [
                        'display' => [
                            'value' => 'grid',
                            'unit' => '',
                        ],
                        'grid-template-areas' => [
                            'value' => trim($gridTemplateAreas),
                            'unit' => '',
                        ],
                        'grid-template-columns' => [
                            'value' => $includeLeft && $includeRight ? '1fr 3fr 1fr' : ($includeLeft || $includeRight ? '1fr 3fr 1fr' : '1fr 2fr 1fr'),
                            'unit' => '',
                        ],
                        'grid-template-rows' => [
                            'value' => 'auto',
                            'unit' => '',
                        ],
                    ],
                ],
            ],
            '(min-width: 768px) and (orientation: landscape)' => [
                'root' => [
                    'properties' => [
                        'grid-template-areas' => [
                            'value' => trim($gridTemplateAreas),
                            'unit' => '',
                        ],
                        'grid-template-columns' => [
                            'value' => $includeLeft && $includeRight ? '1fr 3fr 1fr' : ($includeLeft || $includeRight ? '1fr 3fr 1fr' : '1fr 2fr 1fr'),
                            'unit' => '',
                        ],
                    ],
                ],
            ],
        ];

        $this->elements = $elements;
        $this->styles = $styles;
        $this->main = (string) $mainElementId;
    }


}
