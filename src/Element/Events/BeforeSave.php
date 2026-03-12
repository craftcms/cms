<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event BeforeSave The event that is triggered before the element is saved.
 *
 * Set `$isValid` to `false` to prevent the element from getting saved.
 *
 * {@see Element::beforeSave()}
 */
class BeforeSave
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the element is brand new */
        public bool $isNew,
    ) {}
}
