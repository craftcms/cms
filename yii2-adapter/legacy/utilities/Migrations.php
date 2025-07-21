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
 * Migrations represents a Migrations utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. [[\Craft\Cms\Utility\Utilities\Migrations]] should be used instead.
 */
class Migrations extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft\Cms\Utility\Utilities\Migrations::displayName();
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return Craft\Cms\Utility\Utilities\Migrations::id();
    }

    /**
     * @inheritdoc
     */
    public static function icon(): ?string
    {
        return Craft\Cms\Utility\Utilities\Migrations::icon();
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        return Craft\Cms\Utility\Utilities\Migrations::badgeCount();
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        return Craft\Cms\Utility\Utilities\Migrations::contentHtml();
    }
}
