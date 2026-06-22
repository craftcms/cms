<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

class CpDataResolving
{
    public function __construct(
        public array $data = [],
    ) {}
}
