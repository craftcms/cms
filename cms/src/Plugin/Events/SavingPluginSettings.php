<?php

namespace CraftCms\Cms\Plugin\Events;

use CraftCms\Cms\Support\Events\Concerns\ValidatableEvent;

/**
 * @event SavingPluginSettings The event that is triggered before a plugin’s settings are saved
 */
final class SavingPluginSettings extends PluginEvent
{
    use ValidatableEvent;
}
