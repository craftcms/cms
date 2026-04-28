<?php

declare(strict_types=1);

namespace CraftCms\Cms\Plugin\Events;

use CraftCms\Cms\Plugin\Contracts\PluginInterface;

class PluginRegistered
{
    public function __construct(
        public PluginInterface $plugin,
    ) {}
}
