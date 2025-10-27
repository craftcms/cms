<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use craft\base\Event as YiiEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\web\View;
use CraftCms\Cms\Plugin\Plugin;

/**
 * @mixin Plugin
 *
 * @internal
 */
trait HasViews
{
    public function bootHasViews(): void
    {
        // Base template directory
        YiiEvent::on(View::class, View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, function (RegisterTemplateRootsEvent $e) {
            $basePath = self::getInstance()->getBasePath();
            $handle = self::getInstance()->handle;

            /**
             * Get the first matching directory for views or templates.
             */
            $baseDir = match (true) {
                // Laravel Convention
                is_dir($baseDir = dirname($basePath).'/resources/views') => $baseDir,
                // Laravel Convention for resources, Twig convention for templates
                is_dir($baseDir = dirname($basePath).'/resources/templates') => $baseDir,
                // Craft 5 and earlier
                is_dir($baseDir = $basePath.'/templates') => $baseDir,
                default => false,
            };

            if ($baseDir) {
                $e->roots[$handle] = $baseDir;
            }
        });
    }
}
