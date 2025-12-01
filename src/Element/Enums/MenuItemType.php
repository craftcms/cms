<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

use craft\helpers\Cp;

/**
 * MenuItemType defines all possible disclosure menu item types.
 *
 * @see Cp::disclosureMenu()
 */
enum MenuItemType: string
{
    case Link = 'link';
    case Button = 'button';
    case HR = 'hr';
    case Group = 'group';
}
