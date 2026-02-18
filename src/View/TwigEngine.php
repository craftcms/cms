<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use craft\helpers\FileHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\TemplateRenderer;
use Illuminate\Contracts\View\Engine;

final readonly class TwigEngine implements Engine
{
    public const string TEMPLATE = '__CRAFT_TEMPLATE__';

    public function __construct(
        private TemplateRenderer $renderer,
    ) {}

    public function get($path, array $data = []): string
    {
        $template = Str::after(FileHelper::normalizePath($path), TemplateMode::get()->templatesPath());

        $asTemplate = Arr::pull($data, self::TEMPLATE, false);

        if ($asTemplate) {
            return $this->renderer->renderTemplate($template, $data);
        }

        return $this->renderer->renderPageTemplate($template, $data);
    }
}
