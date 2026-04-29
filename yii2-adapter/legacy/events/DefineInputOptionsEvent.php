<?php

namespace craft\events;

use craft\base\Event;
use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * DefineInputOptionsEvent class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.4.0
 * @deprecated 6.0.0 use {@see \CraftCms\Cms\Field\Events\DefineInputOptions} instead.
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
    public ?ElementInterface $element = null;
}
