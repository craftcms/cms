<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\events;

use yii\base\Event;

/**
 * Update event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class UpdateEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var string|null The type of update ("manual" or "auto")
     */
    public $type;

    /**
     * @var string|null The handle of whatever initiated the update ("craft" or a plugin’s handle)
     */
    public $handle;
}
