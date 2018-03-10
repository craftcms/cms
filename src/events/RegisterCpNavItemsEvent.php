<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterCpNavItemsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
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
