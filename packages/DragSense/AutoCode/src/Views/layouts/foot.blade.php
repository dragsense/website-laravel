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
<script crossorigin src="https://unpkg.com/react@18/umd/react.production.min.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@18/umd/react-dom.production.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/mathjs/13.1.1/math.min.js" integrity="sha512-g9Hl9sW0nMwhkRPakKcrKyxx6NMT/vD/zu7nhggnjI9as96SdYA/KocpFXxJ1TXFZN9ki0Bv1G7NPchjiSE2VQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="/autocode/autocode-custom-index.js"></script>
{!! $footerScripts !!}
{!! $pageFooterScripts !!}
<script src="/autocode/autocode-app-client.js"></script> 
<script>
    window.react = window.React;
</script>
<script src="/autocode/autocode-client.js"></script> 

@endsection
