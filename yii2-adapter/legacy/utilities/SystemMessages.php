<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use craft\base\Utility;

/**
 * SystemMessages represents a System Messages utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 * @deprecated in 6.0.0. [[\CraftCms\Cms\Utility\Utilities\SystemMessages]] should be used instead.
 */
class SystemMessages extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return \CraftCms\Cms\Utility\Utilities\SystemMessages::displayName();
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return \CraftCms\Cms\Utility\Utilities\SystemMessages::id();
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return \CraftCms\Cms\Utility\Utilities\SystemMessages::icon();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return \CraftCms\Cms\Utility\Utilities\SystemMessages::contentHtml();
    }
}
