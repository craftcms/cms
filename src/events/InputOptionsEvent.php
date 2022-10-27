<?php

namespace craft\events;

use craft\base\ElementInterface;
use craft\elements\MatrixBlock;
use yii\base\Event;

class InputOptionsEvent extends Event
{
    /**
     * @var array The options that will be available for the current field
     */
    public array $options;

    /**
     * @var ElementInterface|null The element that the field is generating an input for.
     */
    public ?ElementInterface $element = null;

    /**
     * @var mixed The current value of the field.
     */
    public mixed $value;
}
