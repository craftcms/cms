<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\FieldLayout\FieldLayout;

/**
 * @event FieldLayoutSaved The event that is triggered after a field layout is saved.
 *
 * @phpstan-import-type GeneratedField from FieldLayout
 */
class FieldLayoutSaved extends FieldLayoutEvent
{
    public function __construct(
        FieldLayout $layout,
        public bool $isNew,
        /** @var array{tabs: list<array<string, mixed>>, generatedFields: list<GeneratedField>, cardView: list<string>, thumbFieldKey: string|null, cardThumbAlignment: string}|null */
        public ?array $previousConfig = null,
    ) {
        parent::__construct($layout);
    }
}
