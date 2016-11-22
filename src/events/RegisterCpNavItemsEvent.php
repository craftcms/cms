<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * RegisterCpNavItemsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class RegisterCpNavItemsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The registered CP nav items
     */
    public $navItems = [];


}
