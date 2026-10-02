<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

/**
 * Class Path
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Path} instead.
 */
class Path
{
    /**
     * Ensures that a relative path never goes deeper than its root directory.
     *
     * @param string $path
     * @return bool
     * @deprecated in 6.0.0 use {@see \CraftCms\Cms\Support\Facades\Path::ensurePathIsContained()} instead.
     */
    public static function ensurePathIsContained(string $path): bool
    {
        return \CraftCms\Cms\Support\Facades\Path::ensurePathIsContained($path);
    }
}
