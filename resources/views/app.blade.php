<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script>window.Cp = {};</script>
        {!! $headHtml !!}
        <script>let Craft = (window.Craft || {})</script>
        @vite(['resources/css/cp.css', 'resources/js/cp.ts'], 'vendor/craft/build')
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
            src="data:text/javascript;base64,{{ base64_encode('Cp.config(CpConfig); Cp.start()') }}"
            defer
        ></script>
    </body>
</html>
