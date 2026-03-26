<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\TemplateRenderer;
use Illuminate\Contracts\View\Engine;

readonly class TwigEngine implements Engine
{
    public function __construct(
        private TemplateRenderer $renderer,
    ) {}

    public function get($path, array $data = []): string
    {
        $template = Str::after(File::normalizePath($path), TemplateMode::get()->templatesPath());

        return $this->renderer->renderPageTemplate($template, $data);
    }
}
