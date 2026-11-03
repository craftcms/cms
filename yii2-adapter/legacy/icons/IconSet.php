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
 * IconSet is the base class for custom icon sets, which can be registered via
 * [[\craft\services\Icons::EVENT_REGISTER_ICON_SETS]].
 *
 * Icons selected from a custom icon set are stored as `set-handle:icon-name`.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 *
 * @since 5.12.0
 * @deprecated in 6.0.0. Extend {@see \CraftCms\Cms\Cp\IconSets\IconSet} instead.
 */
abstract class IconSet extends \CraftCms\Cms\Cp\IconSets\IconSet
{
    /**
     * @param array<string, mixed> $config Name-value pairs that will be used to initialize the object properties
     */
    public function __construct(array $config = [])
    {
        Craft::configure($this, $config);
    }
}
