<?php

/**
 * @link https://craftcms.com/
 *
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\icons;

use Craft;

/**
 * DirectoryIconSet is an icon set made up of the SVG files within a directory.
 *
 * Each file’s name (sans `.svg` extension) becomes its icon name.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.12.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Cp\IconSets\DirectoryIconSet} instead.
 */
class DirectoryIconSet extends \CraftCms\Cms\Cp\IconSets\DirectoryIconSet
{
    /**
     * @param array<string, mixed> $config Name-value pairs that will be used to initialize the object properties
     */
    public function __construct(array $config = [])
    {
        Craft::configure($this, $config);
    }
}
