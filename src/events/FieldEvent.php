<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\base\FieldInterface;
use yii\base\Event;

/**
 * FieldEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class FieldEvent extends Event
{
    // Properties
    // =========================================================================

    /**
     * @var FieldInterface|null The field associated with this event.
     */
    public $field;

    /**
     * @var bool Whether the field is brand new
     */
    public $isNew = false;
}
