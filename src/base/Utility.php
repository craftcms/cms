<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\base;

use Craft;

/**
 * Utility is the base class for classes representing Control Panel utilities.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
abstract class Utility extends Component implements UtilityInterface
{
    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function iconPath(): string
    {
        // Default to a circle with the first letter of the utility’s display name
        return Craft::$app->getView()->renderTemplate('_includes/defaulticon.svg', [
            'label' => static::displayName()
        ]);
    }

    /**
     * @inheritdoc
     */
    public static function badgeCount(): int
    {
        // 0 = no badge
        return 0;
    }
}
