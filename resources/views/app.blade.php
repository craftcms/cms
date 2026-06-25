<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {!! $headHtml !!}
        {!! \CraftCms\Cms\Cp\Cp::viteScripts()->toHtml() !!}
        {!! app(\CraftCms\Cms\Plugin\Plugins::class)->getAssetsHtml() !!}
        <script>
            window.Craft = window.Craft || {};
            window.Cp = window.Cp || {};
        </script>
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
        {!! $bodyHtml !!}
        <script>
          let CpConfig = {!! json_encode(\CraftCms\Cms\Cp\Cp::config()) !!};
        </script>
        <script
            src="data:text/javascript;base64,{{ base64_encode('console.log(Cp); window.Cp.config(CpConfig); window.Cp.start()') }}"
            defer
        ></script>
    </body>
</html>
