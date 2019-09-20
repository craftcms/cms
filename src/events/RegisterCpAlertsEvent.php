<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterCpAlertsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterCpAlertsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array The registered CP alerts
     */
    public $alerts = [];
}
