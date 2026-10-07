<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Concerns;

/**
 * @internal
 * @deprecated 6.0.0
 * @phpstan-ignore trait.unused
 */
trait LegacyNestedElementManager
{
    public const string EVENT_DEFINE_BEHAVIORS = 'defineBehaviors';

    public const EVENT_AFTER_SAVE_ELEMENTS = 'afterSaveElements';

    public const EVENT_AFTER_DUPLICATE_NESTED_ELEMENTS = 'afterDuplicateNestedElements';

    public const EVENT_AFTER_CREATE_REVISIONS = 'afterCreateRevisions';
}
