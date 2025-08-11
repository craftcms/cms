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
     * @since 3.5.0
     * @deprecated 6.0.0 {@see \CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface} should be used instead.
     */
    interface ConfigurableComponentInterface extends ComponentInterface
    {
    }
}

class_alias(\CraftCms\Cms\Component\Contracts\ConfigurableComponentInterface::class, ConfigurableComponentInterface::class);
