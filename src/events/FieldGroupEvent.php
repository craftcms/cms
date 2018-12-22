<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\FieldGroup;
use yii\base\Event;

/**
 * FieldGroupEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldGroupEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var FieldGroup|null The field group associated with this event.
     */
    public $group;

    /**
     * @var bool Whether the field group is brand new
     */
    public $isNew = false;
}
