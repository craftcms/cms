<?php

declare(strict_types=1);

namespace CraftCms\Cms\Element\Events;

/**
 * Event dispatched by `craft:resave:all` to discover additional resave commands.
 *
 * Plugins should listen for this event and add already-registered command signatures:
 *
 * ```php
 * Event::listen(DefineResaveCommands::class, function (DefineResaveCommands $event) {
 *     $event->commands['craft:resave:products'] = [
 *         'description' => 'Resave products',
 *     ];
 * });
 * ```
 */
class DefineResaveCommands
{
    /**
     * @var array<string, array{description?: string}> Command signatures mapped to metadata.
     */
    public array $commands = [];
}
