<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

final class RegisterCpSettings
{
    public function __construct(
        /** @var array $settings The registered control panel settings */
        public array $settings = []
    ) {}
}
