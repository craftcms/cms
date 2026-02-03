<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use craft\base\ElementInterface;

/**
 * @event BeforeRestore The event that is triggered before the element is restored.
 *
 * Set `$isValid` to `false` to prevent the element from getting restored.
 *
 * {@see \CraftCms\Cms\Element\Element::beforeRestore()}
 */
final class BeforeRestore
{
    public function __construct(
        public ElementInterface $element,
        /** @var bool Whether the restore should proceed */
        public bool $isValid = true,
    ) {}
}
