<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasViews
{
    public function bootHasViews(): void
    {
        $baseDirs = array_values(array_filter([
            $this->getResourcesPath().'/views',
            $this->getResourcesPath().'/templates',
            $this->getBasePath().'/templates',
        ], is_dir(...)));

        $this->app->make(TemplateRoots::class)->register(
            TemplateMode::Cp,
            $this->handle,
            ...$baseDirs,
        );
    }
}
