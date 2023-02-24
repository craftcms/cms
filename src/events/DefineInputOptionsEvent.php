<?php

namespace craft\events;

use craft\base\ElementInterface;
use yii\base\Event;

/**
 * DefineInputOptionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 */
class DefineInputOptionsEvent extends Event
{
    /**
     * @var array The options that will be available for the current field
     */
    public array $options;

    /**
     * @var mixed The current value of the field.
     */
    public mixed $value;

    /**
     * @var ElementInterface|null The element that the field is generating an input for.
     */
    public ?ElementInterface $element;
}
