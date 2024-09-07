<?php

namespace DragSense\AutoCode\Http\Requests\SettingRequests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSettingRequest extends FormRequest{

    public function rules()
    {

        return [
            'webTitle' => 'string|min:3',
            'tagLine' => 'string|nullable',
            'desc' => 'string|nullable',
            'timeZone' => 'string|nullable',
            'lang' => 'string|nullable',
            'author' => 'string|nullable',
            'keywords' => 'string|nullable',
            'homePage' => 'nullable|array',
            'homePage._id' => 'string|nullable',
            'homePage.name' => 'string|nullable',
            'searchPage' => 'nullable|array',
            'searchPage._id' => 'string|nullable',
            'searchPage.name' => 'string|nullable',
            'maintenancePage' => 'nullable|array',
            'maintenancePage._id' => 'string|nullable',
            'maintenancePage.name' => 'string|nullable',
            'errorPage' => 'nullable|array',
            'errorPage._id' => 'string|nullable',
            'errorPage.name' => 'string|nullable',
            'email' => 'nullable|array',
            'email.host' => 'string|nullable',
            'email.port' => 'string|nullable',
            'email.ignoreTLS' => 'boolean|nullable',
            'email.secure' => 'boolean|nullable',
            'email.auth' => 'nullable|array',
            'email.auth.user' => 'string|nullable',
            'email.auth.pass' => 'string|nullable',
            'email.subject' => 'string|nullable',
            'email.from' => 'string|email|nullable',
            'email.to' => 'string|email|nullable',
            'email.cc' => 'string|email|nullable',
            'email.bcc' => 'string|email|nullable',
            'email.replyTo' => 'string|email|nullable',
            'images' => 'nullable|array',
            'images.favicon' => 'nullable|array',
            'images.favicon._id' => 'string|nullable',
            'images.favicon.src' => 'string|nullable',
            'images.favicon.alt' => 'string|nullable',
            'images.favicon.mimetype' => 'string|nullable',
            'images.favicon.name' => 'string|nullable',
            'images.logo' => 'nullable|array',
            'images.logo._id' => 'string|nullable',
            'images.logo.src' => 'string|nullable',
            'images.logo.alt' => 'string|nullable',
            'images.logo.mimetype' => 'string|nullable',
            'images.logo.name' => 'string|nullable',
            'images.mobileLogo' => 'nullable|array',
            'images.mobileLogo._id' => 'string|nullable',
            'images.mobileLogo.src' => 'string|nullable',
            'images.mobileLogo.alt' => 'string|nullable',
            'images.mobileLogo.mimetype' => 'string|nullable',
            'images.mobileLogo.name' => 'string|nullable',
            'images.placeholder' => 'nullable|array',
            'images.placeholder._id' => 'string|nullable',
            'images.placeholder.src' => 'string|nullable',
            'images.placeholder.alt' => 'string|nullable',
            'images.placeholder.mimetype' => 'string|nullable',
            'images.placeholder.name' => 'string|nullable',
            'scripts' => 'nullable|array',
            'scripts.head' => 'string|nullable',
            'scripts.footer' => 'string|nullable',
        ];
    }
}
