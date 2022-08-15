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
 * @since 3.0.0
 */
class FieldEvent extends Event
{
    /**
     * @var FieldInterface The field associated with this event.
     */
    public FieldInterface $field;

    /**
     * @var bool Whether the field is brand new
     */
    public bool $isNew = false;
}
