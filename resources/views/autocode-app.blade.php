@include('autocode::layouts.app')

<!DOCTYPE html>
<html lang="{{ $setting['lang'] ?? str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        @yield('autocode-head')

        @viteReactRefresh
      
    </head>
    <body>
        @yield('autocode-content')
        @yield('autocode-foot')

    </body>
</html>

