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
     * LocalFsInterface is the interface that must be implemented by all filesystems that operate locally.
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Contracts\LocalFsInterface} instead.
     */
    interface LocalFsInterface extends \CraftCms\Cms\Filesystem\Contracts\LocalFsInterface
    {
    }
}

class_alias(\CraftCms\Cms\Filesystem\Contracts\LocalFsInterface::class, LocalFsInterface::class);
