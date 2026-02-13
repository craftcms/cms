<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>window.Craft = {};</script>
        @vite(['resources/css/cp.css', 'resources/js/cp.ts'], 'vendor/craft/build')
        @inertiaHead
    </head>
    <body>
        @hasSection('content')
            {{-- Non-Inertia (legacy) page: render content inside the app div --}}
            <div id="app" data-page="{{ json_encode($page ?? \CraftCms\Cms\Cp\Cp::nonInertiaPageData()) }}">
                @yield('content')
            </div>
        @else
            @inertia
        @endif
        <script>
          let CpConfig = {!! json_encode(\CraftCms\Cms\Cp\Cp::config()) !!};
        </script>
        <script
            src="data:text/javascript;base64,{{ base64_encode('Craft.config(CpConfig); Craft.start()') }}"
            defer
        ></script>
    </body>
</html>
