<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Exceptions\TemplateLoaderException;
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

        try {
            return $this->renderer->renderPageTemplate($template, $data);
        } catch (TemplateLoaderException $e) {
            /**
             * If a custom error page is set up on the frontend, Laravel will
             * try to render it in the Control Panel as well. This ensures
             * we render the Control Panel error templates instead.
             */
            if (TemplateMode::is(TemplateMode::Cp) && Str::contains($template, 'errors/')) {
                $template = Str::after(File::normalizePath($path), TemplateMode::Site->templatesPath());

                return $this->renderer->renderPageTemplate($template, $data);
            }

            throw $e;
        }
    }
}
