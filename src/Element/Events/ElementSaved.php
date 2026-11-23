<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

use CraftCms\Cms\Element\Contracts\ElementInterface;

/**
 * Craft dispatches this after it saves an element and commits the element's own database transaction.
 *
 * An outer transaction can still be open and roll the save back. Applying a draft, saving nested elements with
 * their owner, and calling `saveElement()` inside `DB::transaction()` all save the element inside one. Wrap email,
 * API calls, and other work outside the database in `DB::afterCommit()`, or dispatch a queued job with `afterCommit()`.
 *
 * Craft marks the element as clean once the listeners return, so read its dirty attributes and fields before you
 * defer the work.
 *
 * For database work that belongs in the element's save transaction, listen to {@see ElementPersisted} instead.
 *
 * @since 6.0.0
 */
class ElementSaved
{
    public function __construct(
        public ElementInterface $element,
        public bool $isNew = false,
    ) {}
}
