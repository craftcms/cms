<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fs;

/**
 * Temp represents a temporary filesystem.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 4.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Filesystem\Filesystems\Temp} instead.
 */
class Temp extends \CraftCms\Cms\Filesystem\Filesystems\Temp
{
    use \craft\base\LegacyEventConstants;
}
