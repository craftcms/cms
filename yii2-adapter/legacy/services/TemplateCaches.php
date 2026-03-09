<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use CraftCms\Cms\View\TemplateCaches as ViewTemplateCaches;
use Throwable;
use yii\base\Component;

/**
 * Template Caches service.
 *
 * An instance of the service is available via [[\craft\base\ApplicationTrait::getTemplateCaches()|`Craft::$app->getTemplateCaches()`]].
 *
 * @deprecated in 6.0.0. Use [[\CraftCms\Cms\View\TemplateCaches]] instead.
 * @see \CraftCms\Cms\View\TemplateCaches
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class TemplateCaches extends Component
{
    /**
     * Returns a cached template by its key.
     *
     * @param string $key The template cache key
     * @param bool $global Whether the cache would have been stored globally.
     * @param bool $registerResources Whether JS and CSS resources captured by the cache should be registered
     * @return string|null
     * @deprecated in 6.0.0. Use [[\CraftCms\Cms\View\TemplateCaches::getTemplateCache()]] instead.
     * @see \CraftCms\Cms\View\TemplateCaches::getTemplateCache()
     */
    public function getTemplateCache(string $key, bool $global, bool $registerResources = false): ?string
    {
        return app(ViewTemplateCaches::class)->getTemplateCache($key, $global, $registerResources);
    }

    /**
     * Starts a new template cache.
     *
     * @param bool $withResources Whether JS and CSS resources should be captured and included in the cache.
     * @param bool $global Whether the cache should be stored globally.
     * @deprecated in 6.0.0. Use [[\CraftCms\Cms\View\TemplateCaches::startTemplateCache()]] instead.
     * @see \CraftCms\Cms\View\TemplateCaches::startTemplateCache()
     */
    public function startTemplateCache(bool $withResources = false, bool $global = false): void
    {
        app(ViewTemplateCaches::class)->startTemplateCache($withResources, $global);
    }

    /**
     * Ends a template cache.
     *
     * @param string $key The template cache key.
     * @param bool $global Whether the cache should be stored globally.
     * @param string|null $duration How long the cache should be stored for.
     * @param mixed $expiration When the cache should expire.
     * @param string $body The contents of the cache.
     * @param bool $withResources Whether JS and CSS resources should be captured and included in the cache.
     * @throws Throwable
     * @deprecated in 6.0.0. Use [[\CraftCms\Cms\View\TemplateCaches::endTemplateCache()]] instead.
     * @see \CraftCms\Cms\View\TemplateCaches::endTemplateCache()
     */
    public function endTemplateCache(string $key, bool $global, ?string $duration, mixed $expiration, string $body, bool $withResources = false): void
    {
        app(ViewTemplateCaches::class)->endTemplateCache($key, $global, $duration, $expiration, $body, $withResources);
    }
}
