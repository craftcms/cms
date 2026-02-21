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
     * BaseFsInterface defines the common interface to be implemented by filesystem classes and volume model.
     *
     * @since 4.4.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Contracts\BaseFsInterface} instead.
     */
    interface BaseFsInterface extends \CraftCms\Cms\Filesystem\Contracts\BaseFsInterface
    {
    }
}

class_alias(\CraftCms\Cms\Filesystem\Contracts\BaseFsInterface::class, BaseFsInterface::class);
