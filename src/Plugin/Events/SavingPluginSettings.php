<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Events;

use CraftCms\Cms\Shared\Concerns\ValidatableEvent;

/**
 * @event SavingPluginSettings The event that is triggered before a plugin’s settings are saved
 *
 * @since 6.0.0
 */
class SavingPluginSettings extends PluginEvent
{
    use ValidatableEvent;
}
