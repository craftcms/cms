<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;

/**
 * DbBackup represents a DbBackup dashboard widget.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\Craft\Cms\Utility\Utilities\DbBackup]] should be used instead.
 */
class DbBackup extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft\Cms\Utility\Utilities\DbBackup::displayName();
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return Craft\Cms\Utility\Utilities\DbBackup::id();
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft\Cms\Utility\Utilities\DbBackup::icon();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft\Cms\Utility\Utilities\DbBackup::contentHtml();
    }
}
