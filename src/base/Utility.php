<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

/**
 * Utility is the base class for classes representing control panel utilities.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
abstract class Utility extends Component implements UtilityInterface
{
    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        // 0 = no badge
        return 0;
    }

    /**
     * @inheritdoc
     */
    public static function toolbarHtml(): string
    {
        return '';
    }

    /**
     * @inheritdoc
     */
    public static function footerHtml(): string
    {
        return '';
    }
}
