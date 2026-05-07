<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Field;
use Illuminate\Support\Collection;

/**
 * @event FieldTypesResolving The event that is triggered when registering field types.
 *
 * Field types must implement {@see FieldInterface}. {@see Field} provides a base implementation.
 *
 * See [Field Types](https://craftcms.com/docs/5.x/extend/field-types.html) for documentation on creating field types.
 * ---
 * ```php
 * use CraftCms\Cms\Field\Events\FieldTypesResolving;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(FieldTypesResolving::class, function(FieldTypesResolving $event) {
 *     $event->types->add(MyFieldType::class);
 * });
 * ```
 */
class FieldTypesResolving
{
    public function __construct(
        /** @var Collection<class-string<FieldInterface>> */
        public Collection $types,
    ) {}
}
