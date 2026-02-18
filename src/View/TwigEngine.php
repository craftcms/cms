<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\View\Engine;

class TwigEngine implements Engine
{
    public function get($path, array $data = []): string
    {
        $template = Str::after(FileHelper::normalizePath($path), TemplateMode::get()->templatesPath());

        return Craft::$app->getView()->renderPageTemplate($template, $data);
    }
}
