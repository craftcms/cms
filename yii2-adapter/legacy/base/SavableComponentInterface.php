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
     * @since 3.0.0
     * @deprecated 6.0.0 {@see \CraftCms\Cms\Component\Contracts\SavableComponentInterface} should be used instead.
     */
    interface SavableComponentInterface extends ConfigurableComponentInterface
    {
    }
}

class_alias(\CraftCms\Cms\Component\Contracts\SavableComponentInterface::class, SavableComponentInterface::class);
