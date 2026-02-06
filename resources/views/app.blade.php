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
        @inertia
        <script>
          let CpConfig = {!! json_encode(\CraftCms\Cms\Cp\Cp::config()) !!};
        </script>
        <script
            src="data:text/javascript;base64,{{ base64_encode('Craft.config(CpConfig); Craft.start()') }}"
            defer
        ></script>
    </body>
</html>
