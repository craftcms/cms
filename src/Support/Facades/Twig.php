<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support\Facades;

use CraftCms\Cms\Twig\Environment;
use CraftCms\Cms\Twig\TemplateRenderer;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\Facade;
use Override;
use Twig\Extension\ExtensionInterface;

/**
 * @method static Environment get(?TemplateMode $mode = null)
 * @method static Environment create()
 * @method static void registerExtension(ExtensionInterface $extension, ?TemplateMode $mode = null)
 * @method static void set(Environment $twig, ?TemplateMode $mode = null)
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
