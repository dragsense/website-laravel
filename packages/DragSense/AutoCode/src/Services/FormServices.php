<?php

namespace DragSense\AutoCode\Services;

use Config;
use DB;
use DragSense\AutoCode\Models\Media;
use DragSense\AutoCode\Models\Form;
use DragSense\AutoCode\Http\Exceptions\ApiError;
use Exception;
use File;
use Illuminate\Support\Facades\Log;
use Storage;
use Str;
use Symfony\Component\Mime\Part\TextPart;

class FormServices
{

    protected $settingServices;
    protected $mediaServices;
    protected $documentServices;


    public function __construct(
        SettingServices $settingServices,
        MediaServices $mediaServices,
        DocumentServices $documentServices
    ) {
        $this->settingServices = $settingServices;
        $this->mediaServices = $mediaServices;
        $this->documentServices = $documentServices;

    }



    /**
     * Create a form
     *
     * @param array $formData
     * @return  Form
     */
    public function createForm(array $formData): Form
    {
        if (isset($formData['emailbody'])) {
            $formData['emailbody']['head'] = html_entity_decode($formData['emailbody']['head'] ?? '');
            $formData['emailbody']['body'] = html_entity_decode($formData['emailbody']['body'] ?? '');
            $formData['emailbody']['footer'] = html_entity_decode($formData['emailbody']['footer'] ?? '');
        }

        if (!isset($formData['elements'])) {
            $formData['elements'] = [
                "0" => [
                    "_uid" => "0",
                    "tagName" => "div",
                    "type" => "layout",
                    "name" => 'Form Root Element',

                    "layout" => "root",
                    "nodeValue" => "",
                    "childNodes" => []
                ]
            ];
        }

        return Form::create($formData);
    }

    /**
     * Update a form
     *
     * @param string $id
     * @param array $formData
     * @return  Form
     * @throws ApiError
     */
    public function updateForm(string $id, array $formData): Form
    {
        $form = Form::find($id);
        if (!$form) {
            throw new ApiError('Form not found', 404);
        }

        if (isset($formData['emailbody'])) {
            $formData['emailbody']['head'] = html_entity_decode($formData['emailbody']['head'] ?? '');
            $formData['emailbody']['body'] = html_entity_decode($formData['emailbody']['body'] ?? '');
            $formData['emailbody']['footer'] = html_entity_decode($formData['emailbody']['footer'] ?? '');
        }
        $form->update($formData);
        return $form;
    }

    /**
     * Update form elements
     *
     * @param string $id
     * @param array $formData
     * @return  Form
     * @throws ApiError
     */
    public function updateElements(string $id, array $formData): Form
    {
        $form = Form::find($id);
        if (!$form) {
            throw new ApiError('Form not found', 404);
        }

        $form->published = false;
        $form->_components = $formData['_components'];
        $form->_elements = $formData['_elements'];

        $form->update();
        return $form;
    }

    /**
     * Publish a form
     *
     * @param string $id
     * @param array $formData
     * @return  Form
     * @throws ApiError
     */
    public function publishForm(string $id, array $formData): Form
    {
        $form = Form::find($id);
        if (!$form) {
            throw new ApiError('Form not found', 404);
        }

        if ($form->published) {
            throw new ApiError('Form is already published', 400);
        }

        $form->elements = $form->_elements;
        $form->components = $form->_components;
        $form->styles = $form->_styles;
        $form->published = true;
        $form->updater = $formData['updater'] ?? null;

        $form->update();
        return $form;
    }

    /**
     * Restore a form
     *
     * @param string $id
     * @param array $formData
     * @return  Form
     * @throws ApiError
     */
    public function restoreForm(string $id, array $formData): Form
    {
        $form = Form::find($id);

        if (!$form) {
            throw new ApiError('Form not found', 404);
        }

        if ($form->published) {
            throw new ApiError('There are no new draft changes to restore', 400);
        }

        $form->_elements = $form->elements;
        $form->_components = $form->components;
        $form->_styles = $form->styles;
        $form->updater = $formData['updater'] ?? null;
        $form->published = true;

        $form->update($formData);
        return $form;
    }

    /**
     * Update a form style
     *
     * @param string $id
     * @param array $formData
     * @return  Form
     * @throws ApiError
     */
    public function updateStyle(string $id, array $formData): Form
    {
        $form = Form::find($id);
        if (!$form) {
            throw new ApiError('Form not found', 404);
        }



        $form->published = false;
        $form->_styles = $formData['_styles'];

        $form->update();
        return $form;
    }

    /**
     * Get forms with pagination
     *
     * @param array $filter
     * @param array $options
     * @return  mixed
     */
    public function getForms(array $filter = [], array $options = [])
    {
        $projection = [
            'name',
            'slug',
            'setting',
            'creator',
            'created_at',
            'updated_at',
            'updater',
            '_id',
        ];

        return Form::paginate($filter, $options, $projection);
    }

    /**
     * Get a single form
     *
     * @param string $id
     * @return  Form
     * @throws ApiError
     */
    public function getForm(string $id): Form
    {

        $projection = [
            'name',
            'slug',
            'emailbody',
            'record',
            'recordOnly',
            'type',
            '_id',
            'setting',
            'states',
        ];

        $form = Form::select($projection)->find($id);
        if (!$form) {
            throw new ApiError('Form not found', 404);
        }


        return $form;
    }

    /**
     * Get a form style
     *
     * @param string $id
     * @param $stream
     * @return  mixed
     * @throws ApiError
     */
    public function getStyle(string $id, callable $streamCallback)
    {
        $projection = ['_styles', 'styles', '_id', 'published'];

        $form = Form::find($id, $projection);

        if (!$form) {
            throw new ApiError('Form not found', 404);
        }

        $doc = ['_id' => $form->_id];
        $doc['styles'] = $form->published ? $form->styles : $form->_styles;

        // $styles = array_map(function($style) {
        //     return is_string($style) ? json_decode($style, true) : $style;
        // }, $doc['styles']);

        if (count($doc['styles']) > 0) {
            $chunkSize = 1024;

            $jsonData = json_encode(['elements' => $doc['styles'], '_id' => $doc['_id']]);
            $chunkStr = $jsonData . "\n";
            $streamCallback($chunkStr);
        }
        $streamCallback(null);

    }

    /**
     * Duplicate a form
     *
     * @param string $id
     * @return  Form
     */
    public function duplicateForm(string $id): Form
    {
        return Form::duplicate($id, "Form");
    }



    /**
     * Delete a form
     *
     * @param string $id
     * @return bool
     * @throws ApiError
     */
    public function deleteForm(string $id): bool
    {
        try {
            // Find the form by ID
            $form = Form::find($id);

            if (!$form) {
                throw new ApiError('Form not found', 404);
            }

            $pluralSlug = Str::plural(strtolower($form->slug));

            $tableName = 'ac_' . $pluralSlug;

            // Delete the table associated with the form
            $deleted = DB::statement("DROP TABLE IF EXISTS `{$tableName}`");

            if (!$deleted) {
                throw new ApiError('Documents not found', 404);
            }

            // Delete the form object from the database
            $form->delete();

            return true;
        } catch (Exception $e) {
            // Log the error and throw a generic API error
            error_log('An unexpected error occurred: ' . $e->getMessage());
            throw new ApiError('An unexpected error occurred', 500);
        }
    }


    /**
     * Submits a form, handling files, validation, email sending, and recording.
     *
     * @param int $id The ID of the form to submit.
     * @param array &$files Uploaded files.
     * @param array &$states Form states.
     * @param string $host The host URL for generating file paths.
     * @return void
     * @throws ApiError If an unexpected error occurs.
     */
    public function submitForm($id, &$files, &$states, $host)
    {
        // Validate the form and its states
        $form = $this->validateForm($id);
        $formStates = $this->validateStates($form, $states);

        // Handle files if any are uploaded
        $attachments = $this->handleFiles($files, $formStates, $host);

        try {
            // Send an email if the form is not marked as record-only
            if (!$form->recordOnly) {
                $this->sendEmail($form, $formStates, $attachments);
            }

            // Record the form data or clean up temporary files
            if ($form->record || $form->recordOnly) {
                $this->recordForm($form, $formStates);
            } else {
                $this->cleanUpTempFiles($attachments);
            }

            return $form->setting;

        } catch (Exception $e) {
            // Handle exceptions, log the error, and clean up temporary files
            error_log('An unexpected error occurred: ' . $e->getMessage());
            $this->cleanUpTempFiles($attachments);
            throw new ApiError('An unexpected error occurred', 500);
        }
    }

    /**
     * Validates the existence of the form by ID.
     *
     * @param int $id The ID of the form.
     * @return Form The form object.
     * @throws ApiError If the form is not found.
     */
    protected function validateForm($id)
    {
        $form = Form::find($id);
        if (!$form) {
            throw new ApiError('Form not found', 404);
        }
        return $form;
    }

    /**
     * Validates the states of the form against the form's requirements.
     *
     * @param Form $form The form object.
     * @param array $states The submitted form states.
     * @return array The validated form states.
     * @throws ApiError If validation fails.
     */
    protected function validateStates($form, $states)
    {
        $formStates = $form->states;
        $isValid = true;

        foreach ($formStates as $key => $stateData) {
            if ($stateData['type'] !== 'file') {
                $fieldData = $states[$key] ?? null;
                $regexTest = true;

                if (!empty($stateData['regex'])) {
                    $regex = '/' . trim($stateData['regex'], '/') . '/';
                    $regexTest = preg_match($regex, $fieldData);
                }

                if (($stateData['required'] && !$fieldData) || !$regexTest) {
                    $stateData['error'] = 1;
                    $isValid = false;
                } else {
                    $stateData['error'] = 0;
                    $formStates[$key]['defaultValue'] = $fieldData;
                }
            }
        }

        if (!$isValid) {
            throw new ApiError('Something Went Wrong.', 403);
        }

        return $formStates;
    }

    /**
     * Handles the uploaded files, storing them and updating form states.
     *
     * @param array $files The uploaded files.
     * @param array &$formStates The form states to update.
     * @param string $host The host URL for generating file paths.
     * @return array The list of attachments.
     * @throws ApiError If an unexpected error occurs.
     */
    protected function handleFiles($files, &$formStates, $host)
    {
        $attachments = [];

        try {
            foreach ($files as $file) {
                $type = $this->determineFileType($file->getMimeType());
                $key = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $uploadPath = "public/static/uploads/{$type}";

                if (isset($formStates[$key])) {


                    if (!File::exists(storage_path('app/' . $uploadPath))) {
                        File::makeDirectory(storage_path('app/' . $uploadPath), 0755, true);
                    }

                    // Store the file and create media record
                    $filename = $file->hashName();
                    $file->storeAs($uploadPath, $filename);
                    $media = $this->mediaServices->createMedia($file, $type, $uploadPath . "/" . $filename);

                    $formStates[$key]['fileType'] = $type;
                    $formStates[$key]['defaultValue'] = $media->src;
                    $formStates[$key]['src'] = [
                        '_id' => $media->_id,
                        'src' => $media->src
                    ];

                    $attachments[] = [
                        'filename' => $file->getClientOriginalName(),
                        'mime' => $file->getMimeType(),
                        'path' => storage_path('app/' . $uploadPath) . "/" . $filename,
                        'id' => $media->_id,

                    ];
                }
            }
        } catch (Exception $e) {
            $this->cleanUpTempFiles($attachments);
            error_log('An unexpected error occurred: ' . $e->getMessage());
            throw new ApiError('An unexpected error occurred', 500);
        }

        return $attachments;
    }

    /**
     * Sends an email with the form data and attachments.
     *
     * @param Form $form The form object.
     * @param array $formStates The form states.
     * @param array $attachments The file attachments.
     * @return void
     */
    protected function sendEmail($form, $formStates, $attachments)
    {
        $setting = $this->settingServices->getSettings(); // Ensure $setting is an array

        // Check if 'email' exists in $setting before accessing its keys, otherwise fallback to .env values
        $emailSettings = $form->setting ?? $setting['email'] ?? [];


        $from = $emailSettings['from'] ?? env("MAIL_FROM_ADDRESS");
        $to = $emailSettings['to'] ?? env("MAIL_TO_ADDRESS");
        $cc = $emailSettings['cc'] ?? env("MAIL_CC_ADDRESS");
        $bcc = $emailSettings['bcc'] ?? env("MAIL_BCC_ADDRESS");
        $replyTo = $emailSettings['replyTo'] ?? env("MAIL_REPLY_TO_ADDRESS");
        $subject = $emailSettings['subject'] ?? "Untitled Email // " . env("MAIL_FROM_NAME");


        $emailSettings = $setting['email'] ?? [];


        // Email host and port settings
        $mailHost = $emailSettings['host'] ?? env("MAIL_HOST");
        $mailPort = $emailSettings['port'] ?? env("MAIL_PORT");
        $mailSecure = $emailSettings['secure'] ? 'tls' : env("MAIL_ENCRYPTION"); // Use 'tls' or 'ssl' for secure transport
        $ignoreTLS = $emailSettings['ignoreTLS'] ?? false;

        // Authentication details
        $mailUser = $emailSettings['auth']['user'] ?? env("MAIL_USERNAME");
        $mailPass = $emailSettings['auth']['pass'] ?? env("MAIL_PASSWORD");


        // Ensure $to is not null before proceeding
        if ($from && $to) {
            // Store the original mail config before changing them
            $originalMailConfig = [
                'host' => config('mail.mailers.smtp.host'),
                'port' => config('mail.mailers.smtp.port'),
                'encryption' => config('mail.mailers.smtp.encryption'),
                'username' => config('mail.mailers.smtp.username'),
                'password' => config('mail.mailers.smtp.password'),
                'stream' => config('mail.mailers.smtp.stream'),
            ];

            // Set mail configuration dynamically using config() helper
            config(['mail.mailers.smtp.host' => $mailHost]);
            config(['mail.mailers.smtp.port' => $mailPort]);
            config(['mail.mailers.smtp.encryption' => $mailSecure]);
            config(['mail.mailers.smtp.username' => $mailUser]);
            config(['mail.mailers.smtp.password' => $mailPass]);

            // Optionally adjust stream options if TLS should be ignored
            if ($ignoreTLS) {
                config([
                    'mail.mailers.smtp.stream' => [
                        'ssl' => [
                            'allow_self_signed' => true,
                            'verify_peer' => false,
                        ],
                    ]
                ]);
            }

            // Compile email content
            $emailContent = $this->compileEmailContent($form);

            // Set email content (HTML or plain text)
            $content = $form->emailbody['html'] ? $emailContent['html'] : $emailContent['plain'];

            // Replace placeholders in the email content and subject with actual form states
            foreach ($formStates as $key => $value) {
                $content = str_replace("{{{$key}}}", $value['defaultValue'] ?? '', $content);
                $subject = str_replace("{{{$key}}}", $value['defaultValue'] ?? '', $subject);
            }

            // Optionally send a copy to the user's email if provided
            if (isset($formStates['email']) && !empty($form->setting['sendCopy']) && filter_var($formStates['email'], FILTER_VALIDATE_EMAIL)) {
                $to .= ',' . $formStates['email'];
            }

            // Set up the email data
            $emailData = [
                'from' => $from,
                'to' => $to,
                'subject' => $subject,
                'html' => $form->emailbody['html'] ? $content : null,
                'text' => $form->emailbody['plain'] ? $content : null,
                'cc' => $cc,
                'bcc' => $bcc,
                'replyTo' => $replyTo,
                'attachments' => $attachments,
            ];



            // Send the email
            app('mailer')->send([], [], function ($message) use ($emailData) {
                $message->from($emailData['from'])
                    ->to($emailData['to'])
                    ->subject($emailData['subject']);

                if (!empty($emailData['cc'])) {
                    $message->cc($emailData['cc']);
                }

                if (!empty($emailData['bcc'])) {
                    $message->bcc($emailData['bcc']);
                }

                if (!empty($emailData['replyTo'])) {
                    $message->replyTo($emailData['replyTo']);
                }

                if ($emailData['html']) {
                    $message->html($emailData['html']);
                } elseif ($emailData['text']) {
                    $message->text($emailData['text']);
                }

                if (!empty($emailData['attachments'])) {
                    foreach ($emailData['attachments'] as $attachment) {
                        $message->attach(
                            $attachment['path'], 
                            [
                               'as' => $attachment['filename'],
                               'mime' => $attachment['mime']
                             ] 
                        );
                    }
                }
            });

            // Reset mail configuration to original values after sending the email
            config(['mail.mailers.smtp.host' => $originalMailConfig['host']]);
            config(['mail.mailers.smtp.port' => $originalMailConfig['port']]);
            config(['mail.mailers.smtp.encryption' => $originalMailConfig['encryption']]);
            config(['mail.mailers.smtp.username' => $originalMailConfig['username']]);
            config(['mail.mailers.smtp.password' => $originalMailConfig['password']]);
            config(['mail.mailers.smtp.stream' => $originalMailConfig['stream']]);


        }
    }

    /**
     * Records the form data into the document service.
     *
     * @param Form $form The form object.
     * @param array $formStates The form states.
     * @return void
     */
    protected function recordForm($form, $formStates)
    {
        $uniqueId = Str::uuid()->toString();
        $body = [
            'name' => $form->name . ' ' . now()->toDateString(),
            'slug' => 'form_' . $uniqueId,
            'states' => $formStates,
            'coll' => [
                '_id' => $form->_id,
                'slug' => $form->slug,
            ],
        ];

        $this->documentServices->createDocument($form->_id, true, $body);
    }

    /**
     * Cleans up temporary files after processing.
     *
     * @param array $attachments The list of attachments to clean up.
     * @return void
     */
    protected function cleanUpTempFiles($attachments)
    {
        foreach ($attachments as $attachment) {
            $this->mediaServices->deleteMedia($attachment['id']);
        }
    }

    /**
     * Determines the file type based on its MIME type.
     *
     * @param string $mimetype The MIME type of the file.
     * @return string The determined file type.
     */
    protected function determineFileType($mimetype)
    {
        $type = 'docs';
        if (Str::startsWith($mimetype, 'image')) {
            $type = 'images';
        } elseif (Str::startsWith($mimetype, 'video')) {
            $type = 'videos';
        } elseif (Str::startsWith($mimetype, 'audio')) {
            $type = 'audios';
        }

        return $type;
    }

    /**
     * Compiles the email content from the form's email body.
     *
     * @param Form $form The form object.
     * @return array The compiled email content.
     */
    protected function compileEmailContent($form)
    {
        $plain = $form->emailbody['plain'] ?? 'Undefined';

        $compiledTemplate = "<!DOCTYPE html>
    <html>
        <head>
            <meta charset=\"UTF-8\">
            {$form->emailbody['head']}
        </head>
        <body>
            {$form->emailbody['body']}
            {$form->emailbody['footer']}
        </body>
    </html>";
        return [
            'html' => $compiledTemplate,
            'plain' => $plain,
        ];
    }


}
