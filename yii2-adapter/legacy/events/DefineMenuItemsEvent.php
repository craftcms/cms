<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * DefineMenuItemsEvent is used to define menu items.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\ElementActionMenuItemsResolving} instead.
 */
class DefineMenuItemsEvent extends Event
{
    /**
     * @var array The menu items.
     */
    public array $items = [];
}
