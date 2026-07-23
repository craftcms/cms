<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\Contracts;

use CraftCms\Cms\View\TemplateMode;

interface TemplateRendererInterface
{
    public function supports(string $file): bool;

    public function renderTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string;

    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string;
}
