<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Events;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;

abstract class PluginEvent
{
    public function __construct(
        public PluginInterface $plugin
    ) {}
}
