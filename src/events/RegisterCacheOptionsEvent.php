<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterCacheOptionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterCacheOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array List of registered cache options for the Clear Caches tool. Each option should contain 'key', 'action', and 'label' keys.
     */
    public $options = [];
}
