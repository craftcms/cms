<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Contracts;

use CraftCms\Cms\View\TemplateMode;

interface TemplateRendererInterface
{
    public bool $isRenderingTemplate { get; }

    public bool $isRenderingPageTemplate { get; }

    public function isRenderingTemplate(): bool;

    public function isRenderingPageTemplate(): bool;

    public function supports(string $file): bool;

    public function renderTemplate(string $template, array $variables, ?TemplateMode $templateMode = null): string;

    public function renderPageTemplate(string $template, array $variables, ?TemplateMode $templateMode = null): string;

    public function renderString(string $template, array $variables, TemplateMode $templateMode = TemplateMode::Site): string;
}
