<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Craft;
use CraftCms\Cms\Support\Str;

class Engine implements \Illuminate\Contracts\View\Engine
{
    public function get($path, array $data = []): string
    {
        $template = Str::after($path, 'templates/');

        return Craft::$app->getView()->renderPageTemplate($template, $data);
    }
}
