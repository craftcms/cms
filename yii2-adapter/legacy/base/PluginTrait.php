<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * PluginTrait implements the common methods and properties for plugin classes.
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Plugin\Concerns\PluginTrait} instead.
     */
    trait PluginTrait
    {
    }
}

class_alias(\CraftCms\Cms\Plugin\Concerns\PluginTrait::class, PluginTrait::class);
