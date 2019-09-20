<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * SetElementRouteEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class SetElementRouteEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var mixed The route that should be used for the element, or false if no special action should be taken
     */
    public $route;
}
