<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Enums;

use CraftCms\Cms\Cp\Html\MenuHtml;

/**
 * MenuItemType defines all possible disclosure menu item types.
 *
 * @see MenuHtml::disclosureMenu()
 */
enum MenuItemType: string
{
    case Link = 'link';
    case Button = 'button';
    case HR = 'hr';
    case Group = 'group';
}
