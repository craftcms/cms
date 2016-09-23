<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

/**
 * Field layout Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldLayoutEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var \craft\app\models\FieldLayout The field layout associated with this event.
     */
    public $layout;
}
