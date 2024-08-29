<?php

namespace DragSense\AutoCode\Http\Controllers;

use DragSense\AutoCode\Http\Controllers\Controller;
use DragSense\AutoCode\Models\Collection;
use DragSense\AutoCode\Models\Page;
use DragSense\AutoCode\Services\SettingServices;
use DragSense\AutoCode\Services\ThemeServices;
use DragSense\AutoCode\Utils\Utils;
use Exception;
use File;
use Illuminate\Http\Request;
use Log;

class AutoCodeController extends Controller
{
    protected $settingServices;
    protected $themeServices;

    public function __construct(SettingServices $settingServices, ThemeServices $themeServices)
    {
        $this->settingServices = $settingServices;
        $this->themeServices = $themeServices;
    }

    public function handlePageRequest(Request $request)
    {
        try {


            $dynamicSlugs = trim($request->getPathInfo(), '/');

            $slugParts = explode('/', $dynamicSlugs);

            $projection = ['_id', 'setting', 'slug', 'type'];

            $setting = $this->settingServices->getSettings();
            $page = null;
            $remainingSlug = '';

            // Check if maintenance page is set
            if (isset($setting['maintenancePage']) && !empty($setting['maintenancePage'])) {
                $page = Page::select($projection)
                    ->where('_id', $setting->maintenancePage->_id)
                    ->where('setting->status', 'PUBLIC')
                    ->first();
            } else {
                // Check if home page is set
                if (isset($setting['maintenancePage']) && !empty($setting['maintenancePage']) && (count($slugParts) <= 0 || $dynamicSlugs === '')) {
                    $page = Page::select($projection)
                        ->where('_id', $setting->homePage->_id)
                        ->where('setting->status', 'PUBLIC')
                        ->first();
                } else {
                    $page = Page::select($projection)
                        ->where('slug', $dynamicSlugs)
                        ->where('setting->status', 'PUBLIC')
                        ->first();

                    // If page not found, check in collections
                    if (!$page) {
                        foreach ($slugParts as $i => $slugPart) {
                            $currentSlug = implode('/', array_slice($slugParts, 0, $i + 1));

                            $page = Collection::select($projection)
                                ->where('slug', $currentSlug)
                                ->where('setting->status', 'PUBLIC')
                                ->first();

                            if ($page) {
                                $remainingSlug = implode('/', array_slice($slugParts, $i + 1));
                                break;
                            }
                        }
                    }
                }
            }

            // If page not found, check if error page is set
            if (!$page && isset($setting['errorPage']) && !empty($setting['errorPage'])) {
                $page = Page::select($projection)
                    ->where('_id', $setting->errorPage->_id)
                    ->where('setting.status', 'PUBLIC')
                    ->first();
            }



            // If page not found, handle page not found
            if (!$page) {
                return $this->handlePageNotFound();
            }

            $protocol = request()->getScheme() ?? 'http';
            $host = "{$protocol}://{$request->getHost()}";
            $fullhost = "{$protocol}://{$host}";

            $pageResults = $this->fetchPageData($page, $fullhost, $remainingSlug);

            $html = '';





            $globalSetting = [
                'global' => [
                    'webTitle' => $setting['webTitle'] ?? 'Default Title',
                    'tagLine' => $setting['tagLine'] ?? 'Default Tag Line',
                    'desc' => $setting['desc'] ?? 'Default Description',
                    'author' => $setting['author'] ?? 'Default Author',
                    'images' => $setting['images'] ?? [],
                  
                ],
                'host' => $fullhost
                
            ];


            if (isset($pageResults['pages'][$page->_id])) {
                $elements = $pageResults['pages'][$page->_id]['elements'];
                $pageSetting = $pageResults['pages'][$page->_id]['pageSetting'];
                $html = $this->generateHTML(
                    $elements,
                    "0",
                    $pageResults['pages'],
                    $pageSetting,
                    $globalSetting,
                    $fullhost
                ); // Generate HTML for the root node
            }


            // $htmlString = view('layouts.page', [
            //     'page' => $page,
            //     'pagesResults' => $pagesResults,
            //     'setting' => $settingData,
            //     'type' => $page->type ?? 'page',
            //     'AutocodeCustom' => $request->AutocodeCustom,
            //     'AutoCode' => $request->AutoCode
            // ])->render();

            $options = ['limit' => 10];
            $fonts = $this->themeServices->getFonts([], $options);
            $colors = $this->themeServices->getColors([], $options);
            $variables = $this->themeServices->getVariables([], $options);

            $globalCss = $this->themeServices->getCss();



            $rootCss = "html { " .
                implode(' ', array_map(fn($font) => "{$font['variable']}: {$font['fontFamily']};", $fonts['data'])) .
                implode(' ', array_map(fn($color) => "{$color['variable']}: {$color['color']};", $colors['data'])) .
                implode(' ', array_map(fn($variable) => "{$variable['variable']}: {$variable['value']};", $variables['data'])) .
                " }";

            $fontCssString = implode('', array_map(function ($font) {
                if ($font['isGoogleFont'] && !empty($font['fontSrc'])) {
                    return $font['fontSrc'];
                } elseif (is_array($font['fontSrc']) && count($font['fontSrc']) > 0) {
                    $fontSrcString = implode(',', array_map(fn($src) => "url(\"{$src}\")", $font['fontSrc']));
                    return "<style>
                            @font-face {
                                font-family: \"{$font['fontFamily']}\";
                                src: {$fontSrcString};
                            }
                        </style>";
                }
                return '';
            }, $fonts['data']));

            $globalJs = $this->themeServices->getJs();

            $cssFilePath = base_path('dist/autocode-custom-style.css');
            $jsFilePath = base_path('dist/autocode-custom-index.js');

            // Check if CSS directory exists, if not create it
            if (!File::exists(dirname($cssFilePath))) {
                File::makeDirectory(dirname($cssFilePath), 0755, true);
            }

            // Check if JS directory exists, if not create it
            if (!File::exists(dirname($jsFilePath))) {
                File::makeDirectory(dirname($jsFilePath), 0755, true);
            }


            $pageCss = implode("\n", $pageResults['css']);


            /// Write CSS to temp file
            File::put($cssFilePath, "{$rootCss}\n{$globalCss['css']}\n{$pageCss}");

            // Write JS to temp file
            File::put($jsFilePath, $globalJs['code']);


            return view('autocode-app', [
                'type' => $page->type ?? 'page',
                '_id' => $page->_id,
                'globalSetting' => $globalSetting,
                'setting' => $setting,
                'remainingSlug' => $remainingSlug,
                'fontCssString' => $fontCssString,
                'html' => $html
            ]);
        } catch (\Exception $e) {
            \Log::error("Error in handlePageRequest: {$e->getMessage()}");
            return $this->handlePageNotFound();
        }
    }

    function handlePageNotFound()
    {
        return response()->make(
            "<!DOCTYPE html>
            <html>
                <head>
                    <title>404</title>
                </head>
                <body>
                    <div id='root'>Page not found.</div>
                </body>
            </html>",
            404
        )->header('Content-Type', 'text/html');
    }


    function generateHTML(&$elements, $nodeId, &$pageResults, &$pageSetting, &$globalSetting, &$host, &$states = [])
    {
        if (!isset($elements[$nodeId])) {
            \Log::error("Element with ID {$nodeId} not found in elements.");
            return '';
        }

        $element = $elements[$nodeId];
        if (empty($element['tagName'])) {
            \Log::error("Element with ID {$nodeId} does not have a tagName.");
            return '';
        }

        $conditions = $element['conditions'] ?? [];
        $mapValue = [];
        $mapIndex = 0;
        if (!$this->calculateConditions($conditions, $states, $pageSetting, $globalSetting, $mapValue, $mapIndex)) {
            // Skip rendering this element if conditions are not met
            return '';
        }

        $html = '';

        // Check if the element has a 'map' key and iterate if present
        if (isset($element['map'])) {
            $elem = $element['map'];
            $props = [];
            $mapItems = $this->getStatesValueType($elem, $states, $props, $host);


            foreach ($mapItems as $mapIndex => $mapValue) {
                $html .= $this->renderElement(
                    $element,
                    $elements,
                    $pageResults,
                    $pageSetting,
                    $globalSetting,
                    $states,
                    $mapValue,
                    $mapIndex
                );
            }
        } else {
            $html .= $this->renderElement($element, $elements, $pageResults, $pageSetting, $globalSetting, $states);
        }

        return $html;
    }

    private function renderElement(
        $element,
        &$elements,
        &$pageResults,
        &$pageSetting,
        &$globalSetting,
        &$states,
        &$mapValue = [],
        &$mapIndex = 0
    ) {
        $tagName = $element['tagName'];
        $state = $mapItem['state'] ?? $element['state'] ?? ["type" => '', "key" => ''];
        $value = $this->getValue(
            $state,
            $states,
            $pageSetting,
            $globalSetting,
            $mapValue,
            $mapIndex,
            $element['nodeValue']
        );

        $html = "<{$tagName}";

        $attributes = $this->calculateAttributes(
            $element['attributes'] ?? [],
            $states,
            $pageSetting,
            $globalSetting,
            $mapValue,
            $mapIndex,
        );
        foreach ($attributes as $key => $attributeValue) {
            if ($attributeValue !== null) {
                $html .= " {$key}=\"" . htmlspecialchars($attributeValue) . "\"";
            }
        }

        $dynamicClasses = isset($element['classes']) ? $this->reduceClassNames(
            $element['classes'],
            $states,
            $pageSetting,
            $globalSetting,
            $mapValue,
            $mapIndex
        ) : '';
        $className = $element['className'] ?? '';

        $allClasses = trim($className . ' ' . $dynamicClasses);
        if (!empty($allClasses)) {
            $html .= " class=\"{$allClasses}\"";
        }

        

        // Handle special cases for img and input tags
        if ($tagName === 'img' && $value) {
            $html .= " src=\"" . htmlspecialchars($value['value'] ?? $value) . "\"";
        } elseif ($tagName === 'input' && $value) {
            $html .= " value=\"" . htmlspecialchars($value['value'] ?? $value) . "\"";
        }

        $html .= '>';

        if(isset($value['label']))
        $value = $value['label'];

        // Add nodeValue inside the tag if it exists and it's not an img or input
        $tagsIncludes = ['img', 'input'];
        if (!in_array($tagName, $tagsIncludes) && $value) {
            $html .= '<span>' . htmlspecialchars($value['value'] ?? $value) . '</span>';
        }

        // Recursively process child nodes
        foreach ($element['childNodes'] ?? [] as $childNodeId) {
            if ($childNodeId === "0") {
                $elementUid = $element['_uid'];
                $pageElements = $elements[$elementUid] ?? null;
                $pageId = $pageElements && isset($pageElements["page"]) ? $pageElements["page"]['_id'] : -1;
                $innerElements = $pageResults[$pageId]['elements'];
                $states = $pageResults[$pageId]['states'];
                
                $html .= $this->generateHTML($innerElements, "0", $pageResults, $pageSetting, $globalSetting, $host, $states);
            } else {
                if (isset($elements[$childNodeId]['page'])) {
                    $page = $elements[$childNodeId]['page'];
                    $pageId = $page['_id'];
                    $pageType = $page['pageType'];

                    if ($pageType === 'form') {
                        $apiPrefix = config('autocode.prefix');

                        $html .= "<span data-name='form'><form action='{$apiPrefix}/v1/forms/submit/{$pageId}' method='POST'>";
                    } else {
                        $html .= "<span data-name='component'>";
                    }
                }
                $html .= $this->generateHTML($elements, $childNodeId, $pageResults, $pageSetting, $globalSetting,  $host, $states);

                if (isset($elements[$childNodeId]['page'])) {
                    $html .= $pageType === 'form' ? '</form></span>' : '</span>';
                }
            }
        }

        $html .= "</{$tagName}>";

        return $html;
    }

    function getStatesValueType($elem, &$states, &$props, $host, $value = [])
    {
        $type = $elem["type"];
        $key = $elem["key"];


        switch ($type) {
            case 'STATES':
                return Utils::getStateStates($key, $states, $host);
            case 'PROPS':
                return Utils::getStateStates($key, $props, $host);
            default:
                return $value;

        }
    }


    function getValue(
        $elem,
        &$states,
        &$setting,
        &$globalSetting,
        &$mapValue,
        &$mapIndex,
        $defaultValue = ''
    ) {

        $type = $elem['type'];
        $key = $elem['key'];

        switch ($type) {
            case 'GLOBAL':
                return Utils::getGlobalValue($key, $globalSetting);
            case 'STATES':

                return Utils::getStateValue($key, $states, $setting['host'] ?? null);
            case 'PAGE':
                return Utils::getPageValue($key, $setting, $setting['host'] ?? null);
                case "FORMSTATES":
                    return isset($states[$key[0]]) && isset($states[$key[0]][$key[1]])
                      ? $states[$key[0]][$key[1]] :
                          "''";
            case 'MAPVALUE':
                return $mapValue;
            case 'MAPINDEX':
                return $mapIndex;
            default:
                return $defaultValue;
        }
    }

    /**
     * This function is used to evaluate the condition expression.
     * 
     * @param string $expression - The condition expression.
     * @param string $v - The state value.
     * @param string $keyValue - The key value.
     * @return bool The result of the condition evaluation.
     */
    private function evaluateCondition(string $expression, string $v, string $keyValue): bool
    {
        // Replace [state] and [key] with actual values
        $expression = str_replace(['[state]', '[key]'], [$v, $keyValue], $expression);

        // Handle boolean literals and convert them to actual boolean values
        $expression = str_replace(['true', 'false', "'true'", "'false'"], [true, false, true, false], $expression);

        // Handle cases where the expression is a direct boolean value or string that represents a boolean
        if ($expression === 'true' || $expression === "'true'") {
            return true;
        }
        if ($expression === 'false' || $expression === "'false'") {
            return false;
        }

        // Supported operators
        $operators = ['===', '==', '!=', '>', '<', '>=', '<=', '&&', '||'];

        foreach ($operators as $operator) {
            if (strpos($expression, $operator) !== false) {
                // Handle logical AND and OR
                if ($operator === '&&' || $operator === '||') {
                    list($left, $right) = explode($operator, $expression);
                    $left = trim($left);
                    $right = trim($right);

                    $leftResult = $this->evaluateCondition($left, $v, $keyValue);
                    $rightResult = $this->evaluateCondition($right, $v, $keyValue);

                    return $operator === '&&' ? ($leftResult && $rightResult) : ($leftResult || $rightResult);
                }

                // Handle comparison operators
                list($left, $right) = explode($operator, $expression);
                $left = trim($left);
                $right = trim($right);
                $result = false;

                switch ($operator) {
                    case '===':
                        $result = $left === $right;
                        break;
                    case '==':
                        $result = $left == $right;
                        break;
                    case '!=':
                        $result = $left != $right;
                        break;
                    case '>':
                        $result = $left > $right;
                        break;
                    case '<':
                        $result = $left < $right;
                        break;
                    case '>=':
                        $result = $left >= $right;
                        break;
                    case '<=':
                        $result = $left <= $right;
                        break;
                }

                return $result;
            }
        }

        // If the expression does not contain recognized operators, handle it as a simple boolean or value
        // For single values, return true if the value is non-empty and non-zero, otherwise false
        if (is_numeric($expression)) {
            return (float) $expression != 0;
        }

        // Handle simple non-empty strings as true
        if (is_string($expression)) {
            return !empty($expression);
        }

        // Default to true for unrecognized expressions
        Log::error("Invalid expression: " . $expression);
        return true;
    }

    /**
     * This function is used to calculate the conditions for rendering the component.
     * 
     * @param array $conditions - The conditions array.
     * @return bool The result of the conditions evaluation.
     */
    private function calculateConditions(
        array $conditions,
        &$states,
        &$setting,
        &$globalSetting,
        &$mapValue,
        &$mapIndex
    ): bool {
        if (empty($conditions))
            return true;
        return array_reduce($conditions, function ($prev, $condition) {
            $value = $this->getValue(
                $condition,
                $states,
                $setting,
                $globalSetting,
                $mapValue,
                $mapIndex
            );
            $keyValue = isset($value['value']) ? $value['value'] : $value;
            $stateValue =
                isset($condition['valueState']) ?
                $this->getValue(
                    $condition['valueState'],
                    $states,
                    $setting,
                    $globalSetting,
                    $mapValue,
                    $mapIndex,
                    ''
                )
                : $condition['value'];
            $v = isset($stateValue['value']) ? $stateValue['value'] : $stateValue;
            $cond = true;

            if (isset($condition['value'])) {
                $cond = $this->evaluateCondition($condition['value'], $v, $keyValue);
            }

            return $condition['relation'] === 'AND' ? $prev && $cond : $prev || $cond;
        }, true);
    }

    /**
     * This function is used to calculate the attribute value.
     * 
     * @param array $attribute - The attribute array.
     * @return mixed The calculated attribute value.
     */
    private function calculateAttributeValue(
        array $attribute,
        &$states,
        &$setting,
        &$globalSetting,
        &$mapValue,
        &$mapIndex
    ) {
        $attributeValue = null;

        if ($attribute['key'] === 'style') {
            $attributeValue = $this->calculateAttributes(
                $attribute['attributes'],
                $states,
                $setting,
                $globalSetting,
                $mapValue,
                $mapIndex
            );
        } else {
            $stateValue =
                isset($attribute['valueState']) ?
                $this->getValue(
                    $attribute['valueState'],
                    $states,
                    $setting,
                    $globalSetting,
                    $mapValue,
                    $mapIndex,
                    $attribute['value']
                )
                : $attribute['value'];
            $value = $stateValue['value'] ?? $stateValue;

            if (is_string($attribute['value']) && !empty($attribute['value'])) {
                $value = str_replace('[state]', $value, $attribute['value']);

                try {
                    eval ("\$attributeValue = $value;");
                } catch (\Exception $e) {
                    // Handle the error according to your application's requirements.
                    error_log("Invalid expression: " . $e->getMessage());
                }
            } else {
                $attributeValue = $value;
            }
        }

        $conditions = $attribute['conditions'] ?? [];
        $cond = $this->calculateConditions(
            $conditions,
            $states,
            $setting,
            $globalSetting,
            $mapValue,
            $mapIndex
        );

        return $cond ? $attributeValue : null;
    }

    /**
     * This function is used to calculate the attributes of the component.
     * 
     * @param array $attributes - The attributes array.
     * @return array The calculated attributes array.
     */
    private function calculateAttributes(
        array $attributes,
        &$states,
        &$setting,
        &$globalSetting,
        &$mapValue,
        &$mapIndex
    ): array {
        if (empty($attributes))
            return [];

        return array_reduce($attributes, function ($a, $attribute) {

            $attributeValue = $this->calculateAttributeValue(
                $attribute,
                $states,
                $setting,
                $globalSetting,
                $mapValue,
                $mapIndex
            );
            return array_merge($a, [$attribute['key'] => $attributeValue]);
        }, []);
    }

    /**
     * Calculate class names based on conditions and state value.
     *
     * @param array $cls The class details containing conditions and state value.
     * @return string The calculated class name or an empty string if conditions are not met.
     */
    function calculateClassNames(
        $cls,
        &$states,
        &$setting,
        &$globalSetting,
        &$mapValue,
        &$mapIndex
    ) {
        if (!isset($cls['conditions']))
            return '';

        $conditions = $cls['conditions'];
        $cond = $this->calculateConditions(
            $conditions,
            $states,
            $setting,
            $globalSetting,
            $mapValue,
            $mapIndex
        );
        $classNames = $this->getValue(
            $cls['stateValue'],
            $states,
            $setting,
            $globalSetting,
            $mapValue,
            $mapIndex,
            $cls['name']
        );

        if (is_string($cls['name'])) {
            $classNames = preg_replace('/\[.*?\]/', $classNames['value'] ?? $classNames, $cls['name']);
        }

        return $cond ? $classNames : '';
    }

    /**
     * Reduce an array of class details into a single string of class names.
     *
     * @param array $classes The array of class details.
     * @return string The concatenated class names.
     */
    function reduceClassNames(
        $classes,
        &$states,
        &$setting,
        &$globalSetting,
        &$mapValue,
        &$mapIndex
    ) {
        return array_reduce($classes, function ($carry, $cls) {
            $classValue = $this->calculateClassNames(
                $cls,
                $states,
                $setting,
                $globalSetting,
                $mapValue,
                $mapIndex
            );
            return $carry . " " . $classValue;
        }, "");
    }

    private function fetchPageData($page, $host, $remainingSlug)
    {
        // Implement fetching page data logic similar to Node.js
        return $this->settingServices->getPageData($page->_id, $page->type, $host, true, $remainingSlug);
    }

}
