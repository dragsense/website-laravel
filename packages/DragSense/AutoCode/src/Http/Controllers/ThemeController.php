<?php 

namespace DragSense\AutoCode\Http\Controllers;

use DragSense\AutoCode\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DragSense\AutoCode\Services\ThemeServices;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Arr;

class ThemeController extends Controller
{
    protected $themeServices;

    /**
     * Constructor to initialize ThemeServices.
     *
     * @param ThemeServices $themeServices
     */
    public function __construct(ThemeServices $themeServices)
    {
        $this->themeServices = $themeServices;
    }

    // Color-related methods

    /**
     * Create a new color.
     *
     * @param Request $request The request containing color data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function createColor(Request $request)
    {
        $color = $this->themeServices->createColor($request->all());
        return response()->json(['color' => $color], Response::HTTP_CREATED);
    }

    /**
     * Update an existing color by its unique ID.
     *
     * @param Request $request The request containing the updated color data.
     * @param int $uid The unique ID of the color to update.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateColor(Request $request, $uid)
    {
        $color = $this->themeServices->updateColor($uid, $request->all());
        return response()->json(['color' => $color], Response::HTTP_OK);
    }

    /**
     * Delete a color by its unique ID.
     *
     * @param int $uid The unique ID of the color to delete.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteColor($uid)
    {
        $deletedUid = $this->themeServices->deleteColor($uid);
        return response()->json(['_uid' => $deletedUid], Response::HTTP_OK);
    }

    /**
     * Get a list of colors with optional filters and pagination.
     *
     * @param Request $request The request containing filter and pagination options.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getColors(Request $request)
    {
        $filter = Arr::only($request->query(), ['name']);
        $options = Arr::only($request->query(), ['sortBy', 'limit', 'page']);

        $colors = $this->themeServices->getColors($filter, $options);
        return response()->json(['colors' => $colors], Response::HTTP_OK);
    }

    // Font-related methods

    /**
     * Create a new font.
     *
     * @param Request $request The request containing font data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function createFont(Request $request)
    {
        $font = $this->themeServices->createFont($request->all());
        return response()->json(['font' => $font], Response::HTTP_CREATED);
    }

    /**
     * Update an existing font by its unique ID.
     *
     * @param Request $request The request containing the updated font data.
     * @param int $uid The unique ID of the font to update.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateFont(Request $request, $uid)
    {
        $font = $this->themeServices->updateFont($uid, $request->all());
        return response()->json(['font' => $font], Response::HTTP_OK);
    }

    /**
     * Delete a font by its unique ID.
     *
     * @param int $uid The unique ID of the font to delete.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteFont($uid)
    {
        $deletedUid = $this->themeServices->deleteFont($uid);
        return response()->json(['_uid' => $deletedUid], Response::HTTP_OK);
    }

    /**
     * Get a list of fonts with optional filters and pagination.
     *
     * @param Request $request The request containing filter and pagination options.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFonts(Request $request)
    {
        $filter = Arr::only($request->query(), ['name']);
        $options = Arr::only($request->query(), ['sortBy', 'limit', 'page']);

        $fonts = $this->themeServices->getFonts($filter, $options);
        return response()->json(['fonts' => $fonts], Response::HTTP_OK);
    }

    // Icon-related methods

    /**
     * Create a new icon.
     *
     * @param Request $request The request containing icon data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function createIcon(Request $request)
    {
        $icon = $this->themeServices->createIcon($request->all());
        return response()->json(['icon' => $icon], Response::HTTP_CREATED);
    }

    /**
     * Update an existing icon by its unique ID.
     *
     * @param Request $request The request containing the updated icon data.
     * @param int $uid The unique ID of the icon to update.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateIcon(Request $request, $uid)
    {
        $icon = $this->themeServices->updateIcon($uid, $request->all());
        return response()->json(['icon' => $icon], Response::HTTP_OK);
    }

    /**
     * Delete an icon by its unique ID.
     *
     * @param int $uid The unique ID of the icon to delete.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteIcon($uid)
    {
        $deletedUid = $this->themeServices->deleteIcon($uid);
        return response()->json(['_uid' => $deletedUid], Response::HTTP_OK);
    }

    /**
     * Get a list of icons with optional filters and pagination.
     *
     * @param Request $request The request containing filter and pagination options.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getIcons(Request $request)
    {
        $filter = Arr::only($request->query(), ['name']);
        $options = Arr::only($request->query(), ['sortBy', 'limit', 'page']);

        $icons = $this->themeServices->getIcons($filter, $options);
        return response()->json(['icons' => $icons], Response::HTTP_OK);
    }

    // Variable-related methods

    /**
     * Create a new variable.
     *
     * @param Request $request The request containing variable data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function createVariable(Request $request)
    {
        $variable = $this->themeServices->createVariable($request->all());
        return response()->json(['variable' => $variable], Response::HTTP_CREATED);
    }

    /**
     * Update an existing variable by its unique ID.
     *
     * @param Request $request The request containing the updated variable data.
     * @param int $uid The unique ID of the variable to update.
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateVariable(Request $request, $uid)
    {
        $variable = $this->themeServices->updateVariable($uid, $request->all());
        return response()->json(['variable' => $variable], Response::HTTP_OK);
    }

    /**
     * Delete a variable by its unique ID.
     *
     * @param int $uid The unique ID of the variable to delete.
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteVariable($uid)
    {
        $deletedUid = $this->themeServices->deleteVariable($uid);
        return response()->json(['_uid' => $deletedUid], Response::HTTP_OK);
    }

    /**
     * Get a list of variables with optional filters and pagination.
     *
     * @param Request $request The request containing filter and pagination options.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVariables(Request $request)
    {
        $filter = Arr::only($request->query(), ['name']);
        $options = Arr::only($request->query(), ['sortBy', 'limit', 'page']);

        $variables = $this->themeServices->getVariables($filter, $options);
        return response()->json(['variables' => $variables], Response::HTTP_OK);
    }

    // Style-related methods

    /**
     * Save style information.
     *
     * @param Request $request The request containing style data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveStyle(Request $request)
    {
        $result = $this->themeServices->saveStyle($request->all());
        return response()->json($result, Response::HTTP_CREATED);
    }

    /**
     * Get style information by its ID.
     *
     * @param int $id The unique ID of the style.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getStyle($id)
    {
        return new StreamedResponse(function () use ($id) {
            $this->themeServices->getStyle($id, function ($data) {
                if ($data !== null && $data !== '') {
                    $chunkSize = dechex(strlen($data));
                    echo "{$chunkSize}\r\n{$data}\r\n";
                    flush();
                }
            });

            // Properly terminate the chunked response
            echo "0\r\n\r\n";
            flush();

        }, 200, [
            'Content-Type' => 'application/json',
            'Transfer-Encoding' => 'chunked',
            'Connection' => 'keep-alive',
        ]);
    }

    // Animation-related methods

    /**
     * Get animation data by its ID.
     *
     * @param int $id The unique ID of the animation.
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function getAnimations($id)
    {
        return new StreamedResponse(function () use ($id) {
            $this->themeServices->getAnimations($id, function ($data) {
                if ($data !== null && $data !== '') {
                    $chunkSize = dechex(strlen($data));
                    echo "{$chunkSize}\r\n{$data}\r\n";
                    flush();
                }
            });

            // Properly terminate the chunked response
            echo "0\r\n\r\n";
            flush();

        }, 200, [
            'Content-Type' => 'application/json',
            'Transfer-Encoding' => 'chunked',
            'Connection' => 'keep-alive',
        ]);
    }

    /**
     * Get a list of animation names.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnimationsNames()
    {
        $names = $this->themeServices->getAnimationsNames();
        return response()->json(['names' => $names], Response::HTTP_OK);
    }

    /**
     * Save animation information.
     *
     * @param Request $request The request containing animation data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveAnimations(Request $request)
    {
        $result = $this->themeServices->saveAnimations($request->all());
        return response()->json($result, Response::HTTP_CREATED);
    }

    /**
     * Get CSS for animations.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAnimationsCss()
    {
        $result = $this->themeServices->getAnimationsCss();
        return response()->json($result, Response::HTTP_OK);
    }

    // CSS-related methods

    /**
     * Save CSS information.
     *
     * @param Request $request The request containing CSS data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveCss(Request $request)
    {
        $result = $this->themeServices->saveCss($request->all());
        return response()->json($result, Response::HTTP_CREATED);
    }

    /**
     * Get CSS information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCss()
    {
        $result = $this->themeServices->getCss();
        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Get custom CSS information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomCss()
    {
        $result = $this->themeServices->getCustomCss();
        return response()->json($result, Response::HTTP_OK);
    }

    // JavaScript-related methods

    /**
     * Save JavaScript information.
     *
     * @param Request $request The request containing JavaScript data.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveJs(Request $request)
    {
        $result = $this->themeServices->saveJs($request->all());
        return response()->json($result, Response::HTTP_CREATED);
    }

    /**
     * Get JavaScript information.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getJs()
    {
        $result = $this->themeServices->getJs();
        return response()->json($result, Response::HTTP_OK);
    }
}
