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
     * Local represents a local filesystem.
     *
     * @property-read mixed $settingsHtml
     * @property-read string $rootPath
     *
     * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
     *
     * @since 4.0.0
     * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Filesystems\Local} instead.
     */
    class Local
    {
    }
}

class_alias(\CraftCms\Cms\Filesystem\Filesystems\Local::class, Local::class);
