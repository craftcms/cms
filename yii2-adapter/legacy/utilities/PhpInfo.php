<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use craft\base\Utility;

/**
 * PhpInfo represents a PhpInfo dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Utilities\PhpInfo]] should be used instead.
 */
class PhpInfo extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return \CraftCms\Cms\Utility\Utilities\PhpInfo::displayName();
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return \CraftCms\Cms\Utility\Utilities\PhpInfo::id();
    }

    /**
     * @inheritdoc
     */
    public static function isSelectable(): bool
    {
        return \CraftCms\Cms\Utility\Utilities\PhpInfo::isSelectable();
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return \CraftCms\Cms\Utility\Utilities\PhpInfo::icon();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return \CraftCms\Cms\Utility\Utilities\PhpInfo::contentHtml();
    }
}
