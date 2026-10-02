<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Events;

use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Import\FieldHandlers\FieldImportHandlerInterface;

/**
 * @event RegisterFieldImportHandlers The event that is triggered when registering field import handlers.
 *
 * Handlers must implement {@see FieldImportHandlerInterface}, and are indexed by the field class they handle.
 * A handler registered for a class is used for its subclasses too, unless one is registered for the subclass itself;
 * registering a handler for a class that already has one replaces it.
 * ---
 * ```php
 * use CraftCms\Cms\Import\Events\RegisterFieldImportHandlers;
 * use Illuminate\Support\Facades\Event;
 *
 * Event::listen(RegisterFieldImportHandlers::class, function(RegisterFieldImportHandlers $event) {
 *     $event->handlers[MyField::class] = MyFieldImportHandler::class;
 * });
 * ```
 *
 * @since 6.0.0
 */
class RegisterFieldImportHandlers
{
    /**
     * Carries the mutable map of registered field import handler classes for listeners to add to.
     *
     * @param  array<class-string<FieldInterface>, class-string<FieldImportHandlerInterface>>  $handlers  The registered handler classes, indexed by the field class they handle.
     */
    public function __construct(
        public array $handlers,
    ) {}
}
