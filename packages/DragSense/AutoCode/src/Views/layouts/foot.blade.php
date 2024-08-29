@section('autocode-foot')

@php
    // Assigning variables with isset checks to ensure array keys exist
    $scriptId = isset($_id) ? "ac-data-script-{$_id}" : 'ac-data-script';
    $type = isset($type) ? $type : 'page';
    $_id = isset($_id) ? $_id : '';
    $globalSetting = isset($globalSetting) ? $globalSetting : [];
    $remainingSlug = isset($remainingSlug) ? $remainingSlug : '';
    $footerScripts = isset($setting['scripts']['footer']) ? $setting['scripts']['footer'] : '';
    $pageFooterScripts = isset($page['setting']['scripts']['footer']) ? $page['setting']['scripts']['footer'] : '';
@endphp

<script id="{{ $scriptId }}">
    window.__INITIAL_DATA__ = {!! json_encode([
        'type' => $type,
        '_id' => $_id,
        'setting' => $globalSetting,
        'remainingSlug' => $remainingSlug,
    ]) !!};
</script>

<script src="/autocode/autocode-custom-index.js"></script>
{!! $footerScripts !!}
{!! $pageFooterScripts !!}
<script src="/autocode/autocode-app-client.js"></script> 
<script src="/autocode/autocode-client.js"></script> 

@endsection
