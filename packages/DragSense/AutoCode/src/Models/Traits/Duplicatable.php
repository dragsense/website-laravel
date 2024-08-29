<?php

namespace DragSense\AutoCode\Models\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

trait Duplicatable
{
    /**
     * Duplicate a document by ID.
     *
     * @param  int|string  $id
     * @param  string  $name
     * @return mixed|null
     */
    public static  function duplicate($id, $name)
    {
        try {
            $doc = self::find($id);

            if (!$doc) {
                throw new \Exception("{$name} not found");
            }

            $uniqueId = Str::uuid()->toString();
            $duplicatedDoc = $doc->toArray();
            unset($duplicatedDoc['id']); // Unset the ID for creating a new document

            // Update the slug of the duplicated document
            if (isset($duplicatedDoc['slug'])) {
                $nameSlug = Str::slug($duplicatedDoc['name']);
                $duplicatedDoc['slug'] = 'copy_' . $nameSlug . '_' . substr($uniqueId, 0, 6);
            }

            // Update the elements and styles of the duplicated document
            if (isset($duplicatedDoc['elements']) && isset($duplicatedDoc['styles'])) {
                self::onCopyElements($duplicatedDoc['elements'], $duplicatedDoc['styles']);
            }

            $duplicatedDoc['duplicated'] = true;
            if (isset($duplicatedDoc['setting']))  {
                $duplicatedDoc['setting']['status'] = "DRAFT";
            }
            // Create the new duplicated document
            return self::create($duplicatedDoc);
        } catch (\Exception $e) {
            Log::error("Error duplicating document: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Copy elements and update their styles.
     *
     * @param  array  &$elements
     * @param  array  &$styles
     * @return void
     */
    protected static  function onCopyElements(array &$elements, array &$styles)
    {
        try {
            foreach ($elements as &$element) {
                $id = Str::random(8);
                $mainClass = $element['mainClass'] ?? 'ac-elem';

                // Update the className and selector of the element
                $element['className'] = "{$mainClass}-{$id}";
                $selectors = explode(' ', $element['selector']);
                $selectors[count($selectors) - 1] = ".{$mainClass}-{$id}";
                $element['selector'] = implode(' ', $selectors);

                $styleUid = $element['styleUid'];

                // Update the selectorText of the style
                if (isset($styles[$styleUid])) {
                    $styles[$styleUid]['selectorText'] = $element['selector'];
                }
            }
        } catch (\Exception $e) {
            Log::error("Error copying elements: {$e->getMessage()}");
        }
    }
}
