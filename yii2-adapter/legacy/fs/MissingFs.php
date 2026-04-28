<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

/**
 * @property class-string<\CraftCms\Cms\Filesystem\Contracts\FsInterface> $expectedType
 *
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Filesystems\MissingFs} instead.
 */
class MissingFs extends \CraftCms\Cms\Filesystem\Filesystems\MissingFs
{
    use \craft\base\LegacyEventConstants;
}
