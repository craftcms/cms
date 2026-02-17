<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\View\TemplateMode;

class Engine implements \Illuminate\Contracts\View\Engine
{
    public function get($path, array $data = []): string
    {
        $template = Str::after(FileHelper::normalizePath($path), TemplateMode::get()->templatesPath());

        return Craft::$app->getView()->renderPageTemplate($template, $data);
    }
}
