<?php

namespace DragSense\AutoCode\Utils;

use DateTime;
use Exception;

class Utils
{
    /**
     * Get the current date in the format 'DD-MM-YYYY'.
     *
     * @return string The current date formatted as 'DD-MM-YYYY'.
     */
    public static function getCurrentDate(): string
    {
        return (new DateTime())->format('d-m-Y');
    }

    /**
     * Format a date string into a specified format.
     *
     * @param string $dateString The date string to format.
     * @param string $format The desired date format.
     * @return string The formatted date or an empty string on failure.
     */
    public static function formatDate(string $dateString, string $format): string
    {
        try {
            $date = new DateTime($dateString);
        } catch (Exception $e) {
            return ''; // Return early if the date string is invalid.
        }

        // Define date format mappings only once to optimize memory usage.
        static $formats = [
            'YYYY-MM-DD' => 'Y-m-d',
            'MM/DD/YYYY' => 'm/d/Y',
            'DD/MM/YYYY' => 'd/m/Y',
            'YYYY/MM/DD' => 'Y/m/d',
            'Month D, YYYY' => 'F j, Y',
            'D MMM, YYYY' => 'j M, Y',
            'MMMM DD, YYYY' => 'F d, Y',
            'DD MMM, YYYY' => 'd M, Y',
        ];

        // Return the formatted date if the format is recognized, else use a default format.
        return $date->format($formats[$format] ?? 'Y-m-d');
    }

    /**
     * Recursively update the defaultValue field based on the type.
     *
     * @param array &$object The object array to update.
     * @param string $type The type to check against.
     * @return void
     */
    public static function updateContentWithType(array &$object, string $type): void
    {
        foreach ($object as &$value) {
            if (is_array($value)) {
                if (!empty($value['states'])) {
                    self::updateContentWithType($value['states'], $type);
                }

                if (($value['type'] ?? '') === $type && isset($value['defaultValue'])) {
                    $value['defaultValue'] = html_entity_decode($value['defaultValue']);
                }
            }
        }
        unset($value); // Break reference to prevent unexpected issues.
    }

    /**
     * Generate CSS for elements.
     *
     * @param array $elements The array of elements.
     * @return string The generated CSS as a string.
     */
    public static function generateElementsCss(array &$elements): string
    {
        if (empty($elements)) {
            return ''; // Return early if no elements are provided.
        }

        $css = '';

        foreach ($elements as $element) {
            $style = $element['style'] ?? [];
            $selectorText = $element['selectorText'] ?? '';

            if ($selectorText) {
                $css .= self::generateElementCss($style, $selectorText);
            }
        }

        return $css;
    }

    /**
     * Generate CSS for a single element.
     *
     * @param array $style The style array.
     * @param string $selectorText The CSS selector.
     * @return string The generated CSS as a string.
     */
    private static function generateElementCss(array &$style, string $selectorText): string
    {
        $css = '';

        foreach ($style as $mediaQuery => $styles) {
            if (empty($styles)) continue; // Skip empty styles.

            if ($mediaQuery !== 'normal') {
                $css .= "@media $mediaQuery {\n";
            }

            $css .= self::generateStyleCss($styles, $selectorText);

            if ($mediaQuery !== 'normal') {
                $css .= "}\n";
            }
        }

        return $css;
    }

    /**
     * Generate CSS for a single style.
     *
     * @param array $styles The styles array.
     * @param string $selectorText The CSS selector.
     * @return string The generated CSS as a string.
     */
    private static function generateStyleCss(array &$styles, string $selectorText): string
    {
        $css = '';

        foreach ($styles as $pseudoClass => $properties) {
            $propertyStrings = array_map(
                fn($prop, $value) => "$prop: " . ($value['value'] ?? '') . (!empty($value['priority']) ? ' !important' : ''),
                array_keys($properties['properties'] ?? []),
                $properties['properties'] ?? []
            );

            $propertiesStr = implode('; ', $propertyStrings);

            if (trim($propertiesStr)) {
                $selector = $pseudoClass !== 'root' ? $selectorText . $pseudoClass : $selectorText;
                $css .= "$selector {\n  $propertiesStr\n}\n\n";
            }
        }

        return $css;
    }

    /**
     * Generate a random key of specified length.
     *
     * @param int $length The desired length of the random key.
     * @return string The generated random key.
     */
    public static function generateRandomKey(int $length): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $result;
    }

    /**
     * Generate CSS from styles.
     *
     * @param array $styles The array of styles.
     * @return string The generated CSS as a string.
     */
    public static function generateCss(array &$styles): string
    {
        if (empty($styles)) {
            return ''; // Return early if no styles are provided.
        }

        $css = '';

        foreach ($styles as $selector => $style) {
            if ($style) {
                $css .= self::generateStyleCss($style, $selector);
            }
        }

        return $css;
    }

    /**
     * Generate CSS for animations.
     *
     * @param array $elements The array of animation elements.
     * @return string The generated CSS for animations.
     */
    public static function generateAnimations(array &$elements): string
    {
        if (empty($elements)) {
            return ''; // Return early if no elements are provided.
        }

        $css = '';

        foreach ($elements as $element) {
            $style = $element['style'] ?? [];
            $css .= self::generateAnimationCss($element['name'] ?? '', $style);
        }

        return $css;
    }

    /**
     * Generate CSS for a single animation.
     *
     * @param string $name The name of the animation.
     * @param array $styles The array of styles for the animation.
     * @return string The generated CSS for the animation.
     */
    private static function generateAnimationCss(string $name, array &$styles): string
    {
        $css = "@keyframes $name {\n";

        foreach ($styles as $keyframe => $properties) {
            $propertyStrings = array_map(
                function ($prop, $value) {
                    $value = is_array($value) ? implode(' ', $value) : $value;
                    return "$prop: $value" . (!empty($value['priority']) ? ' !important' : '');
                },
                array_keys($properties['properties'] ?? []),
                $properties['properties'] ?? []
            );

            $propertiesStr = implode('; ', $propertyStrings);

            $css .= "$keyframe {\n  $propertiesStr\n}\n\n";
        }

        $css .= "}\n";

        return $css;
    }

    /**
     * Generate a list of animation names.
     *
     * @param array $elements The array of animation elements.
     * @return array The list of animation names.
     */
    public static function generateAnimationNames(array &$elements): array
    {
        if (empty($elements)) {
            return []; // Return early if no elements are provided.
        }

        return array_filter(
            array_column($elements, 'name'),
            fn($name) => !empty($name)
        );
    }

    /**
     * Throttle function execution.
     *
     * @param callable $func The function to throttle.
     * @param int $limit The time limit in milliseconds.
     * @return callable The throttled function.
     */
    public static function throttle(callable $func, int $limit): callable
    {
        $inThrottle = false;
        return function (...$args) use ($func, &$inThrottle, $limit) {
            if (!$inThrottle) {
                $inThrottle = true;
                call_user_func_array($func, $args);
                usleep($limit * 1000); // Convert milliseconds to microseconds.
                $inThrottle = false;
            }
        };
    }

    private static $lastCallTime = 0;

    /**
     * Returns a debounced version of the provided callable function.
     *
     * @param callable $func The function to debounce.
     * @param int $wait The wait time in milliseconds.
     * @return callable A debounced version of the provided function.
     */
    public static function debounce(callable $func, int $wait): callable
    {
        $waitInSeconds = $wait / 1000; // Convert milliseconds to seconds

        return function (...$args) use ($func, $waitInSeconds) {
            $currentTime = microtime(true);
            $timeElapsed = $currentTime - self::$lastCallTime;

            if ($timeElapsed < $waitInSeconds) {
                $sleepTime = $waitInSeconds - $timeElapsed;
                sleep($sleepTime); // Sleep for the remaining time
            }

            self::$lastCallTime = microtime(true); // Update the last call time
            call_user_func_array($func, $args); // Call the original function
        };
    }



    /**
     * Get a value from a nested object based on a key array.
     * This function navigates through the settings object using the key array and returns the corresponding value.
     * It handles different types of keys and returns the appropriate value.
     *
     * @param array $elemKey The key array to access the value.
     * @param array $setting The settings array.
     * @param string $host The host URL.
     * @param int $mapIndex The index to use when mapping relationships (default is 0).
     * @return mixed The value based on the key or null if not found.
     */
    public static function getPageValue(array &$elemKey, array &$setting, $host, $mapIndex = 0)
    {
        $key = $elemKey;

        // Handle 'POPULATED' key
        if ($key[0] === 'POPULATED') {
            array_shift($key);
            if (isset($setting['populatedRelationships'][$key[0]])) {
                $populated = $setting['populatedRelationships'][$key[0]][$mapIndex] ?? [];
                array_shift($key);
                return Utils::getPageValue($key, $populated, $host);
            }
        }

        switch ($key[0]) {
            case 'states':
            case 'STATES':
                array_shift($key);
                $states = $setting['states'] ?? [];
                return self::getStateValue($key, $states, $host);
            case 'RELATIONSHIPS':
                return $setting['relationships'][$key[1]] ?? null;
            case 'SETTING':
                return self::getSettingValue($key, $setting, $host);
            case 'creator':
                $creator = $setting['creator'] ?? [];
                return [
                    'value' => $creator['_id'] ?? '',
                    'label' => $creator['name'] ?? 'Unknown',
                ];
            case 'createdAt':
                $createdAt = $setting['createdAt'] ?? null;
                return $createdAt ? self::formatDate($createdAt, $key[1] ?? '') : "[page {$key[1]}]";
            case 'NEXTDOCUMENT':
            case 'PREVDOCUMENT':
                return self::getDocumentValue($key, $setting);
            default:
                return [
                    'value' => $setting[$key[0]] ?? "[page {$key[0]}]",
                    'label' => $setting[$key[0]] ?? "[page {$key[0]}]",
                ];
        }
    }

    /**
     * Retrieve a value from a document based on a key array.
     *
     * @param array $key The key array to access the value.
     * @param array $setting The settings array.
     * @return mixed The value based on the key or null if not found.
     */
    public static function getDocumentValue(array &$key, array &$setting)
    {
        $doc = $setting[$key[0]] ?? null;
        if (!$doc) {
            return null;
        }

        if ($key[1] === 'image') {
            $image = $doc['image'] ?? null;
            return [
                'value' => $image['src'] ?? '/images/default/default-img.png',
                'label' => $image['alt'] ?? 'Featured Image',
            ];
        }

        return [
            'value' => $doc[$key[1]] ?? null,
            'label' => $doc[$key[1]] ?? "[document {$key[1]}]",
        ];
    }

    /**
     * Retrieve a value from a nested object based on a key array.
     *
     * @param array $key The key array to access the value.
     * @param array $setting The settings array.
     * @param string $host The host URL.
     * @return mixed The value based on the key or null if not found.
     */
    public static function getSettingValue(array &$key, array &$setting, $host)
    {
        $settingObj = $setting['setting'] ?? null;
        if (!$settingObj) {
            return null;
        }

        if ($key[1] === 'image') {
            $image = $settingObj['populatedImage'] ?? null;
            return [
                'value' => $host . ($image['src'] ?? '/images/default/default-img.png'),
                'label' => $image['alt'] ?? '[featured image]',
            ];
        }

        if ($key[1] === 'status') {
            $status = $settingObj['status'] ?? null;
            return [
                'value' => $status,
                'label' => $status ?? '[page status]',
            ];
        }

        return [
            'value' => $key[1] === 'content' ? $key[1] : $settingObj[$key[1]] ?? null,
            'label' => $settingObj[$key[1]] ?? "[page {$key[1]}]",
        ];
    }

    /**
     * Get the value of a state based on its key.
     *
     * @param array $key The key array to access the state.
     * @param array $states The states array.
     * @param string $host The host URL.
     * @return mixed The value of the state or an empty string if not found.
     */
    public static function getStateValue(array &$key, array &$states, $host)
    {
        if (empty($states)) {
            return '';
        }

        $stateRef = $states[$key[0]] ?? null;
        if (!$stateRef) {
            return '';
        }

        switch ($stateRef['type']) {
            case 'image':
                return [
                    'value' => $host . ($stateRef['src']['src'] ?? './images/default/default-img.png'),
                    'label' => $stateRef['src']['alt'] ?? 'image',
                ];
            case 'video':
                return [
                    'value' => $host . ($stateRef['src']['src'] ?? './images/default/default-poster.png'),
                    'label' => $stateRef['src']['alt'] ?? 'video',
                    'srcs' => $stateRef['srcs'] ?? [],
                ];
            case 'audio':
                return [
                    'value' => './audios/default/sample.mp3',
                    'srcs' => $stateRef['srcs'] ?? [],
                ];
            case 'images':
                if (isset($key[1], $stateRef['states'])) {
                    $index = is_numeric($key[1]) ? (int) $key[1] : -1;
                    if (isset($stateRef['states'][$index])) {
                        $value = explode(':', $stateRef['states'][$index]['value']);
                        return [
                            'value' => $value[0] ?? '',
                            'label' => $value[1] ?? $value[0],
                        ];
                    }
                }
                return '';
            case 'array':
                if (isset($key[1], $stateRef['states'])) {
                    $index = is_numeric($key[1]) ? (int) $key[1] : -1;
                    if (isset($stateRef['states'][$index])) {
                        $value = explode(':', $stateRef['states'][$index]['value']);
                        return [
                            'value' => $value[0] ?? '',
                            'label' => $value[1] ?? $value[0],
                        ];
                    }
                }
                return '';
            case 'object':
                $objKey = array_slice($key, 1);
                return self::getStateValue($objKey, $stateRef['states'], $host);
            default:
                return $stateRef['defaultValue'] ?? '';
        }
    }


    /**
     * Get the value of a global based on its key.
     *
     * @param array $key The key array to access the state.
     * @param array $setting The settings array.
     * @return mixed The value of the state or an empty string if not found.
     */
    public static function getGlobalValue($key, &$setting)
    {
        if ($key[0] === "WEBSITE") {
            $global = $setting['global'] ?? [];

            if ($key[1] === 'images') {
                $images = $global[$key[1]] ?? [];
                $image = $images[$key[2]] ?? [];

                return [
                    'value' => isset($image['src']) ? ($image['src']) : '/images/default/default-img.png',
                    'label' => empty($image) ? "[{$key[2]}]" : ($image['alt'] ?? "Global Image")
                ];
            }

            return [
                'value' => $global[$key[1]] ?? null,
                'label' => $global[$key[1]] ?? "[global {$key[1]}]"
            ];
        } elseif ($key[0] === "WINDOW") {
            // Simplified return for WINDOW type
            if ($key[1] === 'width') {
                return '[Window width]'; // Replace with actual value if available
            } elseif ($key[1] === 'height') {
                return '[Window height]'; // Replace with actual value if available
            }
        }

        return null;
    }

    /**
     * Get values from states based on a key.
     * This function is used to retrieve an array of values from a given states object.
     * It uses the key array to navigate through the nested states object.
     *
     * @param array $key - The key to access the states.
     * @param array $states - The states object.
     * @param string $host - The host URL for image paths.
     * @return array - An array of values.
     */
    public static function getStateStates($key, $states, $host)
    {
        $values = [];

        try {
            // If states object is not provided, return an empty array
            if (empty($states) || !is_array($key) || !isset($key[0])) {
                return $values;
            }

            // Get the value from the states object using the first element of the key array
            $value = isset($states[$key[0]]) ? $states[$key[0]] : null;
          

            // If the value is not found, return an empty array
            if (empty($value)) {
                return $values;
            }

            // Depending on the type of the value, populate the values array
            if ($value['type'] === "array") {
                $values = array_map(function ($state) {
                    $val = explode(":", $state['value']);
                  
                    return [
                        'label' => $val[0],
                        'value' => $val[1] ?? $state['idx'],
                        'key' => $val[1] ?? $state['id'] ?? $state['idx'],
                    ];
                }, $value['states'] ?? []);
            }

            if ($value['type'] === "images") {
                $values = array_map(function ($state) use ($host) {
                    return [
                        'label' => $state['alt'],
                        'value' => $host . $state['src'],
                        'key' => $state['_id'],
                    ];
                }, $value['states'] ?? []);
            }

            // Add other conditions here for different state types if needed.
        } catch (Exception $e) {
            // If an error occurs, display an error message
            error_log("Something went wrong: " . $e->getMessage());
        }

        // Return the values array
        return $values;
    }
}
