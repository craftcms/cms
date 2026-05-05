<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * Event dispatched by `craft:resave:all` to discover additional resave commands.
 *
 * Plugins should listen for this event and add already-registered command signatures:
 *
 * ```php
 * Event::listen(ElementResaveCommandsResolving::class, function (ElementResaveCommandsResolving $event) {
 *     $event->commands['craft:resave:products'] = [
 *         'description' => 'Resave products',
 *     ];
 * });
 * ```
 */
class ElementResaveCommandsResolving
{
    /**
     * @var array<string, array{description?: string}> Command signatures mapped to metadata.
     */
    public array $commands = [];
}
