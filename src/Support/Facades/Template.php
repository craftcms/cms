<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\View\TemplateManager;
use Illuminate\Support\Facades\Facade;
use Override;

/**
 * @method static string getDefaultDriver()
 * @method static \CraftCms\Cms\View\Contracts\TemplateRendererInterface renderer(\CraftCms\Cms\View\TemplateEngine|string|null $renderer = null)
 * @method static \CraftCms\Cms\View\Contracts\TemplateRendererInterface driver(\CraftCms\Cms\View\TemplateEngine|string|null $driver = null)
 * @method static \CraftCms\Cms\View\TemplateManager extend(\CraftCms\Cms\View\TemplateEngine|string $renderer, \Closure $callback)
 * @method static \CraftCms\Cms\View\TemplateManager forgetRenderers()
 * @method static bool isRenderingTemplate()
 * @method static bool isRenderingPageTemplate()
 * @method static string renderTemplate(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode|null $templateMode = null, bool $publicOnly = false, \CraftCms\Cms\View\TemplateEngine|string|null $renderer = null)
 * @method static string renderSandboxedTemplate(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode|null $templateMode = null, bool $publicOnly = false)
 * @method static string renderPageTemplate(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode|null $templateMode = null, bool $publicOnly = false, \CraftCms\Cms\View\TemplateEngine|string|null $renderer = null)
 * @method static string renderString(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site', \CraftCms\Cms\View\TemplateEngine|string|null $renderer = null)
 * @method static string renderTwigString(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site', bool $escapeHtml = false)
 * @method static string renderSandboxedString(string $template, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site', bool $escapeHtml = false)
 * @method static string renderObjectTemplate(string $template, mixed $object, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site', string|false $escaperStrategy = false)
 * @method static string renderSandboxedObjectTemplate(string $template, mixed $object, array $variables = [], \CraftCms\Cms\View\TemplateMode $templateMode = 'site')
 * @method static string normalizeObjectTemplate(string $template)
 * @method static array getDrivers()
 * @method static \Illuminate\Contracts\Container\Container getContainer()
 * @method static \CraftCms\Cms\View\TemplateManager setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \CraftCms\Cms\View\TemplateManager forgetDrivers()
 *
 * @see TemplateManager
 */
class Template extends Facade
{
    #[Override]
    protected static function getFacadeAccessor(): string
    {
        return TemplateManager::class;
    }
}
