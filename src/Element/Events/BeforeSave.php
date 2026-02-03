<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event BeforeSave The event that is triggered before the element is saved.
 *
 * Set `$isValid` to `false` to prevent the element from getting saved.
 *
 * {@see \CraftCms\Cms\Element\Element::beforeSave()}
 */
final class BeforeSave
{
    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the element is brand new */
        public bool $isNew,
        /** @var bool Whether the save should proceed */
        public bool $isValid = true,
    ) {}
}
