<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\Event;

/**
 * SetElementRouteEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 * @deprecated 6.0.0 Use {@see \CraftCms\Cms\Element\Events\SetRoute} instead.
 */
class SetElementRouteEvent extends Event
{
    /**
     * @var mixed The route that should be used for the element, or `null` if no special action should be taken
     */
    public mixed $route = null;
}
