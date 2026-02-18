<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Concerns;

use CraftCms\Cms\Plugin\Plugin;
use CraftCms\Cms\View\Events\RegisterCpTemplateRoots;
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
        Event::listen(function (RegisterCpTemplateRoots $event) {
            $basePath = self::getInstance()->getBasePath();
            $handle = self::getInstance()->handle;

            /**
             * Get the first matching directory for views or templates.
             */
            $baseDir = match (true) {
                // Laravel Convention
                /** @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/13981 */
                is_dir($baseDir = dirname($basePath).'/resources/views') => $baseDir,
                // Laravel Convention for resources, Twig convention for templates
                /** @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/13981 */
                is_dir($baseDir = dirname($basePath).'/resources/templates') => $baseDir,
                // Craft 5 and earlier
                /** @phpstan-ignore-next-line https://github.com/phpstan/phpstan/issues/13981 */
                is_dir($baseDir = $basePath.'/templates') => $baseDir,
                default => false,
            };

            if ($baseDir) {
                $event->roots[$handle] = $baseDir;
            }
        });
    }
}
