<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterUrlRulesEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class InterceptTokenRouteEvent extends Event
{
    // Properties
    // =========================================================================

    public $useTokenRoute = true;
    public $useReturnedRoute = false;
    public $route = null;
}
