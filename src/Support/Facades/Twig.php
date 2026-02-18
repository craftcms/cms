<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Twig\TemplateRenderer;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static \CraftCms\Cms\Twig\Environment get(\CraftCms\Cms\View\TemplateMode|null $mode = null)
 * @method static void set(\CraftCms\Cms\Twig\Environment $twig, \CraftCms\Cms\View\TemplateMode|null $mode = null)
 * @method static \CraftCms\Cms\Twig\Environment create()
 * @method static void registerExtension(\Twig\Extension\ExtensionInterface $extension, \CraftCms\Cms\View\TemplateMode|null $mode = null)
 * @method static void head()
 * @method static void beginBody()
 * @method static void endBody()
 * @method static bool isRenderingTemplate()
 * @method static string renderTemplate(string $template, array $variables = [], ?\CraftCms\Cms\View\TemplateMode $templateMode = null)
 * @method static string renderSandboxedTemplate(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode|null $templateMode = null)
 * @method static string renderPageTemplate(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode|null $templateMode = null)
 * @method static bool isRenderingPageTemplate()
 * @method static string renderString(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site', bool $escapeHtml = false)
 * @method static string renderSandboxedString(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site', bool $escapeHtml = false)
 * @method static string renderObjectTemplate(string $template, mixed $object, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site')
 * @method static string renderSandboxedObjectTemplate(string $template, mixed $object, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site')
 * @method static string normalizeObjectTemplate(string $template)
 *
 * @see \CraftCms\Cms\Twig\Twig
 * @see \CraftCms\Cms\Twig\TemplateRenderer
 */
class Twig extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return TemplateRenderer::class;
    }
}
