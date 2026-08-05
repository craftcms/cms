<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Fields;

/**
 * @event FieldSaveApplying The event that is triggered before a field save is applied to the database.
 *
 * @phpstan-import-type FieldConfig from Fields
 */
class FieldSaveApplying
{
    public function __construct(
        /**
         * @var FieldInterface|null The field associated with this event, as
         *                          configured before the changes are applied to it (if it already exists).
         */
        public ?FieldInterface $field,

        /** @var FieldConfig New field config data that is about to be applied. */
        public array $config,
    ) {}
}
