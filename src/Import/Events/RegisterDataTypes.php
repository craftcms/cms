<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Import\DataTypes\DataTypeInterface;

/**
 * @event RegisterDataTypes The event that is triggered when registering available import data types.
 *
 * Data types must implement {@see DataTypeInterface}.
 * ---
 * ```php
 * use CraftCms\Cms\Import\Events\RegisterDataTypes;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(RegisterDataTypes::class, function(RegisterDataTypes $event) {
 *     $event->types->add(MyDataType::class);
 * });
 * ```
 */
class RegisterDataTypes
{
    public function __construct(
        /** @var array<class-string<DataTypeInterface>> */
        public array $dataTypes,
    ) {}
}
