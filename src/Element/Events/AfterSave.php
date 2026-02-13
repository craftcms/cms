<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event AfterSave The event that is triggered after the element is saved.
 *
 * {@see \CraftCms\Cms\Element\Element::afterSave()}
 */
final class AfterSave
{
    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the element is brand new */
        public bool $isNew,
    ) {}
}
