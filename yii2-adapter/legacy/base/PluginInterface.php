<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use yii\base\Module;

/** @phpstan-ignore-next-line */
if (false) {
    /**
     * PluginInterface defines the common interface to be implemented by plugin classes.
     * A class implementing this interface should also use [[PluginTrait]].
     *
     * @mixin PluginTrait
     * @mixin Module
     * @phpstan-require-extends Module
     * @property string $handle The plugin’s handle (alias of [[id]])
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     * @since 3.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Plugin\Contracts\PluginInterface} instead.
     */
    interface PluginInterface
    {
    }
}

class_alias(\CraftCms\Cms\Plugin\Contracts\PluginInterface::class, PluginInterface::class);
