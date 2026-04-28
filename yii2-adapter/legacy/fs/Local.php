<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

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
class Local extends \CraftCms\Cms\Filesystem\Filesystems\Local
{
    use \craft\base\LegacyEventConstants;
}
