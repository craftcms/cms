<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\app\events;

use craft\app\models\FieldGroup;

/**
 * FieldGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since  3.0
 */
class FieldGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var FieldGroup The field group associated with this event.
     */
    public $group;

    /**
     * @var boolean Whether the field group is brand new
     */
    public $isNew = false;
}
