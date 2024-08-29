<?php

namespace DragSense\AutoCode\Utils;

use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;

class DocumentHelper{
    /**
     * Generate a random string of the given length.
     *
     * @param int $length
     * @return string|null
     */public static function generateRandomString($length)
    {
        try {
            return Str::random($length);
        } catch (\Exception $e) {
            Log::error("Error generating random string: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Copy elements and update their styles.
     *
     * @param array &$elements
     * @param array &$styles
     */public static function onCopyElements(array &$elements, array &$styles)
    {
        try {
            foreach ($elements as &$element) {
                $id = self::generateRandomString(8);

                $mainClass = $element['mainClass'] ?? "ac-elem";

                $element['className'] = $mainClass . "-" . $id;
                $selectors = explode(" ", $element['selector']);
                $selectors[count($selectors) - 1] = "." . $mainClass . "-" . $id;
                $element['selector'] = implode(" ", $selectors);

                $styleUid = $element['styleUid'];

                if (isset($styles[$styleUid])) {
                    $styles[$styleUid]['selectorText'] = $element['selector'];
                }
            }
        } catch (\Exception $e) {
            Log::error("Error copying elements: " . $e->getMessage());
        }
    }
}