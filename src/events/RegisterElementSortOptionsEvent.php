<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use yii\base\Event;

/**
 * RegisterElementSortOptionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class RegisterElementSortOptionsEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var array List of registered sort options for the element type.
     */
    public $sortOptions = [];
}
