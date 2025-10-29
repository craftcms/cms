<?php

declare(strict_types=1);

namespace CraftCms\Cms\Route\Events;

use CraftCms\Cms\Route\Data\Route;

abstract class RouteEvent
{
    public function __construct(
        public Route $route,
    ) {}
}
