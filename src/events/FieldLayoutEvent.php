<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\events;

use craft\models\FieldLayout;
use yii\base\Event;

/**
 * Field layout Event class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0.0
 */
class FieldLayoutEvent extends Event
{
    /**
     * @var FieldLayout The field layout associated with this event.
     */
    public FieldLayout $layout;

    /**
     * @var bool Whether the field is brand new
     */
    public bool $isNew = false;
}
