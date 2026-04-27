<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

class RegisterCpAlerts
{
    public function __construct(
        /** @var array<int, string|array{content: string, showIcon: bool}> */
        public array $alerts = [],
    ) {}
}
