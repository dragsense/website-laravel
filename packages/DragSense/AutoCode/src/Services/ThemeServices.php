<?php

namespace DragSense\AutoCode\Services;

use DragSense\AutoCode\Utils\Utils;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Log;

class ThemeServices
{
    protected $dataDir;
    protected $publicDir;

    public function __construct()
    {
        $this->dataDir = storage_path('app/private/data');
        $this->publicDir = public_path();

        $this->initializeDirectories();
    }

    /**
     * Create necessary directories and files if they don't exist.
     */
    private function initializeDirectories()
    {
        // Create the data directory if it does not exist
        if (!File::exists($this->dataDir)) {
            try {
                File::makeDirectory($this->dataDir, 0755, true);
            } catch (\Exception $e) {
                Log::error('Failed to create data directory: ' . $e->getMessage());
                throw new \Exception('Failed to create data directory.', 500);
            }
        }

        // Initialize default files
        $this->initializeFiles([
            'setting.json', 'fonts.json', 'colors.json', 'variables.json', 
            'style.json', 'animation.json', 'icons.json', 'customStyle.css', 
            'customJs.js'
        ]);
    }

    /**
     * Create files with default content if they do not exist.
     *
     * @param array $files List of filenames to initialize.
     */
    private function initializeFiles(array $files)
    {
        foreach ($files as $file) {
            $filePath = $this->dataDir . '/' . $file;
            if (!File::exists($filePath)) {
                try {
                    // Initialize with empty JSON or empty string based on the file type
                    File::put($filePath, in_array($file, ['customStyle.css', 'customJs.js']) ? '' : json_encode([]));
                } catch (\Exception $e) {
                    Log::error("Failed to create file $file: " . $e->getMessage());
                    throw new \Exception("Failed to create file $file.", 500);
                }
            }
        }
    }

    /**
     * Retrieve JSON-decoded file contents.
     *
     * @param string $filename Filename to read.
     * @return array Decoded JSON data.
     */
    private function getJsonContent($filename)
    {
        return json_decode(File::get($this->dataDir . '/' . $filename), true) ?? [];
    }

    /**
     * Write JSON-encoded data to a file.
     *
     * @param string $filename Filename to write to.
     * @param array $data Data to write.
     */
    private function putJsonContent($filename, array $data)
    {
        File::put($this->dataDir . '/' . $filename, json_encode($data, JSON_PRETTY_PRINT));
    }

     /**
     * Retrieve the content of a file.
     *
     * @param string $filePath The path to the file.
     * @return string The content of the file.
     */
    protected function getFileContent(string $filePath): string
    {
        if (!File::exists($filePath)) {
            throw new \RuntimeException("File not found: $filePath");
        }

        return File::get($filePath);
    }

    /**
     * Write content to a file.
     *
     * @param string $filePath The path to the file.
     * @param string $content The content to write to the file.
     * @return void
     */
    protected function putFileContent(string $filePath, string $content): void
    {
        File::put($filePath, $content);
    }

    /**
     * Paginate the data with filtering and sorting.
     *
     * @param array $data Data to paginate.
     * @param array $filter Filters to apply.
     * @param array $options Pagination options (page, limit, sortBy).
     * @return array Paginated and sorted data.
     */
    private function paginate($data, $filter, $options)
    {
        $page = $options['page'] ?? 1;
        $limit = $options['limit'] ?? 10;
        $sortBy = $options['sortBy'] ?? 'asc';

        $filteredData = collect($data);

        // Apply name filter if provided
        if (isset($filter['name'])) {
            $filteredData = $filteredData->filter(function ($item) use ($filter) {
                return Str::contains(Str::lower($item['name']), Str::lower($filter['name']));
            });
        }

        $total = $filteredData->count();
        $paginatedData = $filteredData->forPage($page, $limit);

        // Sort the data
        if ($sortBy === 'asc') {
            $paginatedData = $paginatedData->sortBy('name')->values();
        } elseif ($sortBy === 'desc') {
            $paginatedData = $paginatedData->sortByDesc('name')->values();
        }

        return [
            'data' => $paginatedData->toArray(),
            'total' => ceil($total / $limit),
        ];
    }

    /**
     * Create a new color entry.
     *
     * @param array $colorBody Data for the new color.
     * @return array Created color entry.
     * @throws ApiError If name already taken.
     */
    public function createColor(array $colorBody)
    {
        $colors = $this->getJsonContent('colors.json');

        if (collect($colors)->firstWhere('name', $colorBody['name'])) {
            throw new ApiError('Name already taken', 400);
        }

        $uid = Str::uuid()->toString();
        $variable = "--var-" . Str::slug($colorBody['name']) . "-color";

        $colors[$uid] = array_merge($colorBody, [
            'variable' => $variable,
            '_uid' => $uid,
        ]);

        $this->putJsonContent('colors.json', $colors);

        return $colors[$uid];
    }

    /**
     * Update an existing color entry.
     *
     * @param string $uid Unique identifier of the color to update.
     * @param array $colorBody Updated color data.
     * @return array Updated color entry.
     * @throws ApiError If color not found or name already taken.
     */
    public function updateColor($uid, array $colorBody)
    {
        $colors = $this->getJsonContent('colors.json');

        if (!isset($colors[$uid])) {
            throw new ApiError('Color not found', 404);
        }

        if (collect($colors)->where('_uid', '!=', $uid)->firstWhere('name', $colorBody['name'])) {
            throw new ApiError('Name already taken', 400);
        }

        $variable = "--var-" . Str::slug($colorBody['name']) . "-color";

        $colors[$uid] = array_merge($colors[$uid], $colorBody, ['variable' => $variable]);

        $this->putJsonContent('colors.json', $colors);

        return $colors[$uid];
    }

    /**
     * Get a list of colors with pagination and filtering.
     *
     * @param array $filter Filters to apply.
     * @param array $options Pagination options.
     * @return array Paginated list of colors.
     */
    public function getColors(array $filter = [], array $options = [])
    {
        $colors = $this->getJsonContent('colors.json');
        return $this->paginate($colors, $filter, $options);
    }

    /**
     * Delete a color entry.
     *
     * @param string $uid Unique identifier of the color to delete.
     * @return string Deleted color UID.
     * @throws ApiError If color not found.
     */
    public function deleteColor($uid)
    {
        $colors = $this->getJsonContent('colors.json');

        if (!isset($colors[$uid])) {
            throw new ApiError('Color not found', 404);
        }

        unset($colors[$uid]);

        $this->putJsonContent('colors.json', $colors);

        return $uid;
    }

    /**
     * Create a new font entry.
     *
     * @param array $fontBody Data for the new font.
     * @return array Created font entry.
     * @throws ApiError If font limit reached or name already taken.
     */
    public function createFont(array $fontBody): array
    {
        $fonts = $this->getJsonContent('fonts.json');

        if (count($fonts) >= 25) {
            throw new ApiError("Font limit reached. You cannot add more fonts.", 400);
        }

        if (collect($fonts)->contains('name', $fontBody['name'])) {
            throw new ApiError('Name already taken', 400);
        }

        $uid = Str::uuid()->toString();
        $variable = '--var-' . strtolower(str_replace(' ', '-', $fontBody['name'])) . '-font';

        if (!empty($fontBody['src'])) {
            $fontBody['src'] = html_entity_decode($fontBody['src']);
        }

        $fonts[$uid] = array_merge($fontBody, [
            'variable' => $variable,
            '_uid' => $uid,
        ]);

        $this->putJsonContent('fonts.json', $fonts);

        return $fonts[$uid];
    }

    /**
     * Update an existing font entry.
     *
     * @param string $uid Unique identifier of the font to update.
     * @param array $fontBody Updated font data.
     * @return array Updated font entry.
     * @throws ApiError If font not found or name already taken.
     */
    public function updateFont(string $uid, array $fontBody): array
    {
        $fonts = $this->getJsonContent('fonts.json');

        if (!isset($fonts[$uid])) {
            throw new ApiError('Font not found', 404);
        }

        if (collect($fonts)->contains(function ($font) use ($uid, $fontBody) {
            return $font['_uid'] !== $uid && $font['name'] === $fontBody['name'];
        })) {
            throw new ApiError('Name already taken', 400);
        }

        $variable = '--var-' . strtolower(str_replace(' ', '-', $fontBody['name'])) . '-font';

        if (!empty($fontBody['src'])) {
            $fontBody['src'] = html_entity_decode($fontBody['src']);
        }

        $fonts[$uid] = array_merge($fonts[$uid], $fontBody, ['variable' => $variable]);

        $this->putJsonContent('fonts.json', $fonts);

        return $fonts[$uid];
    }

    /**
     * Get a list of fonts with pagination and filtering.
     *
     * @param array $filter Filters to apply.
     * @param array $options Pagination options.
     * @return array Paginated list of fonts.
     */
    public function getFonts(array $filter = [], array $options = []): array
    {
        $fonts = $this->getJsonContent('fonts.json');
        return $this->paginate($fonts, $filter, $options);
    }

    /**
     * Delete a font entry.
     *
     * @param string $uid Unique identifier of the font to delete.
     * @return string Deleted font UID.
     * @throws ApiError If font not found.
     */
    public function deleteFont(string $uid): string
    {
        $fonts = $this->getJsonContent('fonts.json');

        if (!isset($fonts[$uid])) {
            throw new ApiError('Font not found', 404);
        }

        unset($fonts[$uid]);

        $this->putJsonContent('fonts.json', $fonts);

        return $uid;
    }

    /**
     * Create a new variable entry.
     *
     * @param array $variableBody Data for the new variable.
     * @return array Created variable entry.
     * @throws ApiError If name already taken.
     */
    public function createVariable(array $variableBody): array
    {
        $variables = $this->getJsonContent($this->dataDir . '/variables.json');

        if (collect($variables)->contains('name', $variableBody['name'])) {
            throw new ApiError('Name already taken', 400);
        }

        $uid = Str::uuid()->toString();
        $variable = '--var-' . strtolower(str_replace(' ', '-', $variableBody['name'])) . '-variable';

        $variables[$uid] = array_merge($variableBody, [
            'variable' => $variable,
            '_uid' => $uid,
        ]);

        $this->putJsonContent($this->dataDir . '/variables.json', $variables);

        return $variables[$uid];
    }

    /**
     * Update an existing variable entry.
     *
     * @param string $uid Unique identifier of the variable to update.
     * @param array $variableBody Updated variable data.
     * @return array Updated variable entry.
     * @throws ApiError If variable not found or name already taken.
     */
    public function updateVariable(string $uid, array $variableBody): array
    {
        $variables = $this->getJsonContent($this->dataDir . '/variables.json');

        if (!isset($variables[$uid])) {
            throw new ApiError('Variable not found', 404);
        }

        if (collect($variables)->contains(function ($variable) use ($uid, $variableBody) {
            return $variable['_uid'] !== $uid && $variable['name'] === $variableBody['name'];
        })) {
            throw new ApiError('Name already taken', 400);
        }

        $variable = '--var-' . strtolower(str_replace(' ', '-', $variableBody['name'])) . '-variable';
        $variables[$uid] = array_merge($variables[$uid], $variableBody, ['variable' => $variable]);

        $this->putJsonContent($this->dataDir . '/variables.json', $variables);

        return $variables[$uid];
    }

    /**
     * Retrieve a list of variables with filtering and pagination.
     *
     * @param array $filter Filters to apply.
     * @param array $options Pagination options.
     * @return array Paginated list of variables.
     */
    public function getVariables(array $filter, array $options): array
    {
        $variables = $this->getJsonContent($this->dataDir . '/variables.json');
        $variables = array_values($variables);

        return $this->paginate($variables, $filter, $options);
    }

    /**
     * Delete a variable entry.
     *
     * @param string $uid Unique identifier of the variable to delete.
     * @return string Deleted variable UID.
     * @throws ApiError If variable not found.
     */
    public function deleteVariable(string $uid): string
    {
        $variables = $this->getJsonContent($this->dataDir . '/variables.json');

        if (!isset($variables[$uid])) {
            throw new ApiError('Variable not found', 404);
        }

        unset($variables[$uid]);
        $this->putJsonContent($this->dataDir . '/variables.json', $variables);

        return $uid;
    }

    /**
     * Create a new icon entry.
     *
     * @param array $iconBody Data for the new icon.
     * @return array Created icon entry.
     * @throws ApiError If name already taken.
     */
    public function createIcon(array $iconBody): array
    {
        $icons = $this->getJsonContent($this->dataDir . '/icons.json');

        if (collect($icons)->contains('name', $iconBody['name'])) {
            throw new ApiError('Name already taken', 400);
        }

        $uid = Str::uuid()->toString();
        $variable = '--var-' . strtolower(str_replace(' ', '-', $iconBody['name'])) . '-icon';

        $icons[$uid] = array_merge($iconBody, [
            'variable' => $variable,
            '_uid' => $uid,
        ]);

        $this->putJsonContent($this->dataDir . '/icons.json', $icons);

        return $icons[$uid];
    }

    /**
     * Update an existing icon entry.
     *
     * @param string $uid Unique identifier of the icon to update.
     * @param array $iconBody Updated icon data.
     * @return array Updated icon entry.
     * @throws ApiError If icon not found or name already taken.
     */
    public function updateIcon(string $uid, array $iconBody): array
    {
        $icons = $this->getJsonContent($this->dataDir . '/icons.json');

        if (!isset($icons[$uid])) {
            throw new ApiError('Icon not found', 404);
        }

        if (collect($icons)->contains(function ($icon) use ($uid, $iconBody) {
            return $icon['_uid'] !== $uid && $icon['name'] === $iconBody['name'];
        })) {
            throw new ApiError('Name already taken', 400);
        }

        $variable = '--var-' . strtolower(str_replace(' ', '-', $iconBody['name'])) . '-icon';
        $icons[$uid] = array_merge($icons[$uid], $iconBody, ['variable' => $variable]);

        $this->putJsonContent($this->dataDir . '/icons.json', $icons);

        return $icons[$uid];
    }

    /**
     * Retrieve a list of icons with filtering and pagination.
     *
     * @param array $filter Filters to apply.
     * @param array $options Pagination options.
     * @return array Paginated list of icons.
     */
    public function getIcons(array $filter, array $options): array
    {
        $icons = $this->getJsonContent($this->dataDir . '/icons.json');
        $icons = array_values($icons);

        return $this->paginate($icons, $filter, $options);
    }

    /**
     * Delete an icon entry.
     *
     * @param string $uid Unique identifier of the icon to delete.
     * @return string Deleted icon UID.
     * @throws ApiError If icon not found.
     */
    public function deleteIcon(string $uid): string
    {
        $icons = $this->getJsonContent($this->dataDir . '/icons.json');

        if (!isset($icons[$uid])) {
            throw new ApiError('Icon not found', 404);
        }

        // Optionally remove the file as well
        if (isset($icons[$uid]['file']) && File::exists(public_path($icons[$uid]['file']))) {
            File::delete(public_path($icons[$uid]['file']));
        }

        unset($icons[$uid]);
        $this->putJsonContent($this->dataDir . '/icons.json', $icons);

        return $uid;
    }

    /**
     * Create a new style entry.
     *
     * @param array $styleBody Data for the new style.
     * @return array Created style entry.
     * @throws ApiError If name already taken.
     */
    public function createStyle(array $styleBody): array
    {
        $styles = $this->getJsonContent($this->dataDir . '/styles.json');

        if (collect($styles)->contains('name', $styleBody['name'])) {
            throw new ApiError('Name already taken', 400);
        }

        $uid = Str::uuid()->toString();
        $variable = '--var-' . strtolower(str_replace(' ', '-', $styleBody['name'])) . '-style';

        $styles[$uid] = array_merge($styleBody, [
            'variable' => $variable,
            '_uid' => $uid,
        ]);

        $this->putJsonContent($this->dataDir . '/styles.json', $styles);

        return $styles[$uid];
    }

    /**
     * Update an existing style entry.
     *
     * @param string $uid Unique identifier of the style to update.
     * @param array $styleBody Updated style data.
     * @return array Updated style entry.
     * @throws ApiError If style not found or name already taken.
     */
    public function updateStyle(string $uid, array $styleBody): array
    {
        $styles = $this->getJsonContent($this->dataDir . '/styles.json');

        if (!isset($styles[$uid])) {
            throw new ApiError('Style not found', 404);
        }

        if (collect($styles)->contains(function ($style) use ($uid, $styleBody) {
            return $style['_uid'] !== $uid && $style['name'] === $styleBody['name'];
        })) {
            throw new ApiError('Name already taken', 400);
        }

        $variable = '--var-' . strtolower(str_replace(' ', '-', $styleBody['name'])) . '-style';
        $styles[$uid] = array_merge($styles[$uid], $styleBody, ['variable' => $variable]);

        $this->putJsonContent($this->dataDir . '/styles.json', $styles);

        return $styles[$uid];
    }

    /**
     * Delete a style entry.
     *
     * @param string $uid Unique identifier of the style to delete.
     * @return string Deleted style UID.
     * @throws ApiError If style not found.
     */
    public function deleteStyle(string $uid): string
    {
        $styles = $this->getJsonContent($this->dataDir . '/styles.json');

        if (!isset($styles[$uid])) {
            throw new ApiError('Style not found', 404);
        }

        unset($styles[$uid]);
        $this->putJsonContent($this->dataDir . '/styles.json', $styles);

        return $uid;
    }

    /**
     * Save multiple styles to the style JSON file.
     *
     * @param array $styleBody Contains styles to save.
     * @return array Status of the save operation.
     */
    public function saveStyle(array $styleBody): array
    {
        $styles = $styleBody['styles'] ?? [];
        $this->putJsonContent($this->dataDir . '/style.json', $styles);
        return ['status' => true];
    }

    /**
     * Retrieve a style and stream it.
     *
     * @param string $_id Identifier for the style to retrieve.
     * @param callable $streamCallback Callback for streaming the data.
     */
    public function getStyle(string $_id, callable $streamCallback)
    {
        $styles = $this->getJsonContent($this->dataDir . '/style.json');

        if (!empty($styles)) {
            $jsonData = json_encode(['_id' => $_id, 'elements' => $styles]);

            $chunkStr = $jsonData . "\n";

            $streamCallback($chunkStr);
        }

        $streamCallback(null);
    }

    /**
     * Save multiple animations to the animation JSON file.
     *
     * @param array $animationsBody Contains animations to save.
     * @return array Status of the save operation.
     */
    public function saveAnimations(array $animationsBody): array
    {
        $animations = $animationsBody['styles'] ?? [];
        $this->putJsonContent($this->dataDir . '/animation.json', $animations);
        return ['status' => true];
    }

    /**
     * Retrieve animations and stream them.
     *
     * @param string $_id Identifier for the animations to retrieve.
     * @param callable $streamCallback Callback for streaming the data.
     */
    public function getAnimations(string $_id, callable $streamCallback)
    {
        $animations = $this->getJsonContent($this->dataDir . '/animation.json');

        if (!empty($animations)) {
            $jsonData = json_encode(['_id' => $_id, 'elements' => $animations]);

            $chunkStr = $jsonData . "\n";

            $streamCallback($chunkStr);
        }

        $streamCallback(null);
    }

/**
     * Retrieve animation names from the animation JSON file.
     *
     * @return array List of animation names.
     */
    public function getAnimationsNames(): array
    {
        $animations = $this->getJsonContent($this->dataDir . '/animation.json');
        return Utils::generateAnimationNames($animations);
    }

    /**
     * Save custom CSS code to the file.
     *
     * @param array $cssBody Contains the CSS code to save.
     * @return array Status of the save operation.
     */
    public function saveCss(array $cssBody): array
    {
        $code = $cssBody['code'] ?? '';
        $this->putFileContent($this->dataDir . '/customStyle.css', $code);
        return ['status' => true];
    }

    /**
     * Retrieve custom CSS code from the file.
     *
     * @return array Contains the custom CSS code.
     */
    public function getCustomCss(): array
    {
        $code = $this->getFileContent($this->dataDir . '/customStyle.css');
        return ['code' => $code];
    }

    /**
     * Generate CSS for animations and retrieve it along with a setting ID.
     *
     * @return array Contains generated CSS and setting ID.
     */
    public function getAnimationsCss(): array
    {
        $animations = $this->getJsonContent($this->dataDir . '/animation.json');
        $setting = $this->getJsonContent($this->dataDir . '/setting.json');

        $css = Utils::generateAnimations($animations);
        $settingData = $setting;

        return ['css' => $css, '_id' => $settingData['_id'] ?? ''];
    }

    /**
     * Generate combined CSS including custom styles, elements styles, and animations.
     *
     * @return array Contains the combined CSS code.
     */
    public function getCss(): array
    {
        $css = $this->getFileContent($this->dataDir . '/customStyle.css');

        $styles = $this->getJsonContent($this->dataDir . '/style.json');
        $css .= Utils::generateElementsCss($styles);

        $animations = $this->getJsonContent($this->dataDir . '/animation.json');
        $css .= Utils::generateAnimations($animations);

        return ['css' => $css];
    }

    /**
     * Save custom JavaScript code to the file.
     *
     * @param array $jsBody Contains the JavaScript code to save.
     * @return array Status of the save operation.
     */
    public function saveJs(array $jsBody): array
    {
        $code = $jsBody['code'] ?? '';
        $this->putFileContent($this->dataDir . '/customJs.js', $code);
        return ['status' => true];
    }

    /**
     * Retrieve custom JavaScript code from the file.
     *
     * @return array Contains the custom JavaScript code.
     */
    public function getJs(): array
    {
        $code = $this->getFileContent($this->dataDir . '/customJs.js');
        return ['code' => $code];
    }

}
