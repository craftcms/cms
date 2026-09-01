<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\View\TemplateMode;
use CraftCms\Cms\View\TemplateRoots;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasViews
{
    /** @var array<string, string|string[]> */
    protected array $siteTemplateRoots = [];

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

        foreach ($this->siteTemplateRoots as $namespace => $paths) {
            $this->app->make(TemplateRoots::class)->register(
                TemplateMode::Site,
                $namespace,
                ...Arr::wrap($paths),
            );
        }
    }
}
