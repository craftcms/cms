@php($theme = data_get($page, 'props.craft.theme', 'system'))
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \CraftCms\Cms\Support\Facades\I18N::getLocale()->getOrientation() }}"@if ($theme === 'dark') data-theme="dark"@endif>
    <head>
        <meta charset="utf-8" />
        @if ($theme === 'system')
            {{-- Before any styles load, so a dark system doesn't flash the light
                 theme. `useTheme()` takes over once the app boots. --}}
            <script>if (matchMedia('(prefers-color-scheme: dark)').matches) document.documentElement.dataset.theme = 'dark';</script>
        @endif
        <meta name="viewport" content="width=device-width, initial-scale=1">
        {!! $headHtml !!}
        {{-- Marks the end of the server-rendered asset band. Assets injected
             during a client-side visit are inserted here rather than appended,
             so they sit above the control panel stylesheet in both cases. --}}
        <meta name="craft-head-anchor">
        {!! \CraftCms\Cms\Cp\Cp::viteScripts() !!}
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
        {{-- The page data carries the CP config once, as `craft.general`. It
             fills `window.Craft` here, before the body's legacy and plugin
             scripts read it, and the boot hands Inertia the parsed page. --}}
        <script>
          let CpPage = JSON.parse(document.querySelector('script[data-page="app"]').textContent);
          Object.assign(window.Craft, CpPage.props.craft?.general);
        </script>
        <cp-messages id="messages"></cp-messages>
        {!! $bodyHtml !!}
        {{-- A module, so it runs once the CP's own modules have loaded --}}
        <script type="module">window.Cp.config(window.Craft); window.Cp.start(CpPage);</script>
    </body>
</html>
