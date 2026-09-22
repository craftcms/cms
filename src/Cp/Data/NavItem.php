<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;

/**
 * A navigation entry.
 *
 * The name the control panel navigation and plugins use — `getCpNavItem()`
 * returns one of these — for what is otherwise an ordinary {@see ActionItem}.
 * It adds nothing: a nav entry, a breadcrumb and a menu item are the same
 * thing described once and drawn differently, and keeping them one type is
 * what stops them drifting apart.
 *
 * @see PluginInterface::getCpNavItem()
 */
class NavItem extends ActionItem {}
