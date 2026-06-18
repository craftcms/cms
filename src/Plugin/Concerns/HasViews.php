<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\View\Events\CpTemplateRootsResolving;
use Illuminate\Support\Facades\Event;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasViews
{
    public function bootHasViews(): void
    {
        Event::listen(function (CpTemplateRootsResolving $event) {
            $plugin = self::getInstance();
            $basePath = $plugin->getBasePath();
            $resourcesPath = $plugin->getResourcesPath();
            $handle = $plugin->handle;

            $baseDirs = array_values(array_filter([
                // Laravel Convention
                $resourcesPath.'/views',
                // Laravel Convention for resources, Twig convention for templates
                $resourcesPath.'/templates',
                // Craft 5 and earlier
                $basePath.'/templates',
            ], is_dir(...)));

            if ($baseDirs !== []) {
                $event->roots[$handle] = $baseDirs;
            }
        });
    }
}
