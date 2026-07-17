<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {!! $headHtml !!}
        {!! \CraftCms\Cms\Cp\Cp::viteScripts()->toHtml() !!}
        {!! app(\CraftCms\Cms\Plugin\Plugins::class)->getAssetsHtml() !!}
        <script>
          let Craft = (window.Craft || {});
          Craft.booting ||= (fn) => (window.bootingCallbacks ||= []).push(fn);
          Craft.booted  ||= (fn) => (window.bootedCallbacks  ||= []).push(fn);
        </script>
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body>
        <x-inertia::app />
        {!! $bodyHtml !!}
        <script>
          Object.assign(window.Craft, {{ Illuminate\Support\Js::from(\CraftCms\Cms\Cp\Cp::config()) }});
        </script>
        <script
            src="data:text/javascript;base64,{{ base64_encode('window.Craft.start()') }}"
            defer
        ></script>
    </body>
</html>
