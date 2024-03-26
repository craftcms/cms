<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\enums;

use craft\helpers\Cp;

/**
 * MenuItemType defines all possible disclosure menu item types.
 *
 * @see Cp::disclosureMenu()
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 */
enum MenuItemType: string
{
    case Link = 'link';
    case Button = 'button';
    case HR = 'hr';
    case Group = 'group';
}
