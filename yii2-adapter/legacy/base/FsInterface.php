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
     * FsInterface defines the common interface to be implemented by filesystem classes.
     *
     * @mixin Fs
     * @phpstan-require-extends Fs
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Contracts\FsInterface} instead.
     */
    interface FsInterface extends \CraftCms\Cms\Filesystem\Contracts\FsInterface
    {
    }
}

class_alias(\CraftCms\Cms\Filesystem\Contracts\FsInterface::class, FsInterface::class);
