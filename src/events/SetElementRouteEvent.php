<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

/**
 * SetElementRouteEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class SetElementRouteEvent extends \yii\base\Event
{
    // Properties
    // =========================================================================

    /**
     * @var mixed The route that should be used for the element, or false if no special action should be taken
     */
    public $route;
}
