<?php

namespace CraftCms\Cms\Twig;

use CraftCms\Cms\Support\Str;

class Engine implements \Illuminate\Contracts\View\Engine
{
    public function get($path, array $data = [])
    {
        /** @var \craft\web\Application $craft */
        $craft = app('Craft');

        $template = Str::after($path, 'templates/');

        return $craft->getView()->renderPageTemplate($template, $data);
    }
}
