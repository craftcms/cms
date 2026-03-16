<?php

declare(strict_types=1);

namespace CraftCms\Cms\Search\Events;

use craft\base\ElementInterface;
use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event The event that is triggered before keywords are indexed for an element attribute or field.
 *
 * You may set [[ValidatableEvent::$isValid]] to `false` to prevent the attribute/field's keywords from being indexed.
 */
class BeforeIndexKeywords
{
    use ValidatableEvent;

    public function __construct(
        public ElementInterface $element,
        public ?string $attribute,
        public ?int $fieldId,
        public string $keywords,
    ) {}
}
