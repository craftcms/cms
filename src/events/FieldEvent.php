<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\base\FieldInterface;

/**
 * FieldEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var FieldInterface The field associated with this event.
     */
    public $field;

    /**
     * @var boolean Whether the field is brand new
     */
    public $isNew = false;
}
