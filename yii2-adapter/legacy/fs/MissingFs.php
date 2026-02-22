<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

/** @phpstan-ignore-next-line **/
if (false) {
    /**
     * @property class-string<\CraftCms\Cms\Filesystem\Contracts\FsInterface> $expectedType
     *
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Filesystems\MissingFs} instead.
     */
    class MissingFs
    {
    }
}

class_alias(\CraftCms\Cms\Filesystem\Filesystems\MissingFs::class, MissingFs::class);
