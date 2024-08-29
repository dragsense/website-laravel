@section('autocode-head')

@php
    // Checking and assigning values with isset to avoid potential errors
    $pageTitle = isset($page['setting']['title']) ? $page['setting']['title'] : (isset($setting['webTitle']) ? $setting['webTitle'] : 'Page');
    $tagLine = isset($setting['tagLine']) ? $setting['tagLine'] : null;
    $pageDescription = isset($page['setting']['desc']) ? $page['setting']['desc'] : (isset($setting['desc']) ? $setting['desc'] : '');
    $author = isset($setting['author']) ? $setting['author'] : '';
    $keywords = isset($setting['keywords']) ? $setting['keywords'] : '';
    $faviconSrc = isset($setting['images']['favicon']['src']) ? $setting['images']['favicon']['src'] : '/favicon.ico';
    $faviconType = isset($setting['images']['favicon']['mimetype']) ? $setting['images']['favicon']['mimetype'] : 'image/x-icon';
    $language = isset($setting['lang']) ? $setting['lang'] : '';
    $customScripts = isset($setting['scripts']['head']) ? $setting['scripts']['head'] : '';
@endphp

<title>{{ $pageTitle }}{{ $tagLine ? " - $tagLine" : '' }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="author" content="{{ $author }}">
<meta name="keywords" content="{{ $keywords }}">
<link rel="icon" href="{{ $faviconSrc }}" type="{{ $faviconType }}">
<meta http-equiv="Content-Language" content="{{ $language }}">
<link rel="stylesheet" type="text/css" href="/autocode/autocode-custom-style.css">

{!! $fontCssString !!}
{!! $customScripts !!}
@endsection
