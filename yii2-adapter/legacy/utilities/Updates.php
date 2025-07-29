<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use craft\base\Utility;

/**
 * Updates represents a Updates dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Utilities\Updates]] should be used instead.
 */
class Updates extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return \CraftCms\Cms\Utility\Utilities\Updates::displayName();
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return \CraftCms\Cms\Utility\Utilities\Updates::id();
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return \CraftCms\Cms\Utility\Utilities\Updates::icon();
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return \CraftCms\Cms\Utility\Utilities\Updates::badgeCount();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return \CraftCms\Cms\Utility\Utilities\Updates::contentHtml();
    }
}
