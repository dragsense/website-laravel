<?php

namespace DragSense\AutoCode\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use DragSense\AutoCode\Services\FormServices;
use DragSense\AutoCode\Services\SettingServices;
use Illuminate\Http\JsonResponse;

class FormController extends Controller
{
    protected $formServices;
    protected $settingServices;

    public function __construct(FormServices $formServices, SettingServices $settingServices)
    {
        $this->formServices = $formServices;
        $this->settingServices = $settingServices;
    }

    /**
     * Retrieve a specific form by its ID.
     *
     * @param string $id The ID of the form.
     * @return JsonResponse
     */
    public function getForm($id): JsonResponse
    {
        $form = $this->formServices->getForm($id);
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return response()->json(['form' => $form, 'host' => $fullhost]);
    }

    /**
     * Retrieve the data associated with a specific form by its ID.
     *
     * @param string $id The ID of the form.
     * @return JsonResponse
     */
    public function getFormData($id): JsonResponse
    {
        $result = $this->formServices->getFormData($id);
        return response()->json($result);
    }

    /**
     * Stream the elements of a specific form as a chunked response.
     *
     * @param string $id The ID of the form.
     * @return StreamedResponse
     */
    public function getElements($id): StreamedResponse
    {
        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        return new StreamedResponse(function () use ($id, $fullhost) {
            $this->settingServices->getElements($id, 'form', $fullhost, function ($data) {
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
     * Stream the style data of a specific form as a chunked response.
     *
     * @param string $id The ID of the form.
     * @return StreamedResponse
     */
    public function getStyle($id): StreamedResponse
    {
        return new StreamedResponse(function () use ($id) {
            $this->formServices->getStyle($id, function ($data) {
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
     * Retrieve the CSS associated with a specific form.
     *
     * @param string $id The ID of the form.
     * @return JsonResponse
     */
    public function getCss($id): JsonResponse
    {
        $css = $this->settingServices->getCss($id, 'form');
        return response()->json($css);
    }

    /**
     * Duplicate an existing form by its ID.
     *
     * @param string $id The ID of the form to be duplicated.
     * @return JsonResponse
     */
    public function duplicateForm($id): JsonResponse
    {
        $form = $this->formServices->duplicateForm($id);
        return response()->json(['form' => $form]);
    }

    /**
     * Create a new form with the provided data.
     *
     * @param Request $request The request instance containing form data.
     * @return JsonResponse
     */
    public function createForm(Request $request): JsonResponse
    {
        $form = $this->formServices->createForm($request->all());
        return response()->json(['form' => $form], 201);
    }

    /**
     * Update an existing form with new data.
     *
     * @param string $id The ID of the form to be updated.
     * @param Request $request The request instance containing updated form data.
     * @return JsonResponse
     */
    public function updateForm($id, Request $request): JsonResponse
    {
        $form = $this->formServices->updateForm($id, $request->all());
        return response()->json(['form' => $form]);
    }

    /**
     * Publish a specific form, making it available for use.
     *
     * @param string $id The ID of the form to be published.
     * @param Request $request The request instance.
     * @return JsonResponse
     */
    public function publishForm($id, Request $request): JsonResponse
    {
        $this->formServices->publishForm($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Restore a specific form, typically from a deleted or archived state.
     *
     * @param string $id The ID of the form to be restored.
     * @param Request $request The request instance.
     * @return JsonResponse
     */
    public function restoreForm($id, Request $request): JsonResponse
    {
        $this->formServices->restoreForm($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update the style of a specific form.
     *
     * @param string $id The ID of the form whose style is to be updated.
     * @param Request $request The request instance containing style data.
     * @return JsonResponse
     */
    public function updateStyle($id, Request $request): JsonResponse
    {
        $this->formServices->updateStyle($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Update the elements of a specific form.
     *
     * @param string $id The ID of the form whose elements are to be updated.
     * @param Request $request The request instance containing element data.
     * @return JsonResponse
     */
    public function updateElements($id, Request $request): JsonResponse
    {
        $this->formServices->updateElements($id, $request->all());
        return response()->json(['status' => true]);
    }

    /**
     * Delete a specific form by its ID.
     *
     * @param string $id The ID of the form to be deleted.
     * @return JsonResponse
     */
    public function deleteForm($id): JsonResponse
    {
        $this->formServices->deleteForm($id);
        return response()->json(null, 204);
    }

    /**
     * Retrieve a list of forms with optional filters and options.
     *
     * @param Request $request The request instance containing filter and option parameters.
     * @return JsonResponse
     */
    public function getForms(Request $request): JsonResponse
    {
        $filter = $request->only(['name']);
        $options = $request->only(['sortBy', 'limit', 'form']);
        $forms = $this->formServices->getForms($filter, $options);
        return response()->json(['forms' => $forms]);
    }

    /**
     * Submit a form with files and state data.
     *
     * @param Request $request The request instance containing form data and files.
     * @param string $id The ID of the form being submitted.
     * @return JsonResponse
     */
    public function submitForm(Request $request, $id): JsonResponse
    {
        // Extract files from the request
        $files = $request->file('files', []);

        // Extract form state data
        $states = $request->except('files');

        $protocol = request()->getScheme() ?? 'http';
        $host = request()->header('Host');
        $fullhost = "{$protocol}://{$host}";

        // Process the form submission using the form service
        $this->formServices->submitForm($id, $files, $states, $fullhost);

        // Send a response with a status of true
        return response()->json(['status' => true], 201);
    }
}
