<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Contracts;

use CraftCms\Cms\View\Contracts\TemplateRendererInterface;
use CraftCms\Cms\View\TemplateMode;

interface TwigRendererInterface extends TemplateRendererInterface
{
    /** @param array<string, mixed> $variables */
    public function renderSandboxedTemplate(
        string $template,
        array $variables = [],
        ?TemplateMode $templateMode = null,
        ?string $resolvedTemplate = null,
    ): string;

    public function renderString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string;

    /** @param array<string, mixed> $variables */
    public function renderSandboxedString(
        string $template,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        bool $escapeHtml = false,
    ): string;

    /** @param array<string, mixed> $variables */
    public function renderObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
        string|false $escaperStrategy = false,
    ): string;

    /** @param array<string, mixed> $variables */
    public function renderSandboxedObjectTemplate(
        string $template,
        mixed $object,
        array $variables = [],
        TemplateMode $templateMode = TemplateMode::Site,
    ): string;

    public function normalizeObjectTemplate(string $template): string;
}
