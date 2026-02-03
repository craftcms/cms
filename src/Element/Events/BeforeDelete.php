<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event BeforeDelete The event that is triggered before the element is deleted.
 *
 * Set `$isValid` to `false` to prevent the element from getting deleted.
 *
 * {@see \CraftCms\Cms\Element\Element::beforeDelete()}
 */
final class BeforeDelete
{
    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the delete should proceed */
        public bool $isValid = true,
    ) {}
}
