<?php 

namespace DragSense\AutoCode\Http\Controllers;

use Illuminate\Http\Request;
use DragSense\AutoCode\Http\Requests\SettingRequests\StoreSettingRequest;
use DragSense\AutoCode\Services\SettingServices;
use Symfony\Component\HttpFoundation\Response;
use DragSense\AutoCode\Http\Controllers\Controller;

class SettingController extends Controller {

    protected $settingServices;

    /**
     * Constructor to initialize SettingServices.
     *
     * @param SettingServices $settingServices
     */
    public function __construct(SettingServices $settingServices)
    {
        $this->settingServices = $settingServices;
    }

    /**
     * Save a setting.
     *
     * @param StoreSettingRequest $request The request containing the setting data to be saved.
     * @return \Illuminate\Http\JsonResponse
     */
    public function saveSettings(StoreSettingRequest $request)
    {
        $setting = $this->settingServices->saveSettings($request->all());
        return response()->json(['setting' => $setting], Response::HTTP_CREATED);
    }

    /**
     * Get all settings.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSettings()
    {
        $setting = $this->settingServices->getSettings();
       
        // Get the current protocol and host to construct the full host URL
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return response()->json(['setting' => $setting, 'host' => $fullhost], Response::HTTP_OK);
    }

    /**
     * Get page data by ID.
     *
     * @param int $_id The ID of the page to retrieve data for.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPageData($_id)
    {
        // Get the current protocol and host to construct the full host URL
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        // Retrieve page data using SettingServices
        $results = $this->settingServices->getPageData($_id, 'page', $fullhost);
        return response()->json($results, Response::HTTP_OK);
    }
}
