<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * RegisterCpNavItemsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated in 6.0.0. Use {@see \CraftCms\Cms\Cp\Events\CpNavItemsResolving} instead.
 */
class RegisterCpNavItemsEvent extends Event
{
    /**
     * @var array The registered control panel nav items
     */
    public array $navItems = [];
}
