<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Events;

/**
 * @event RegisterReadonlyCpSettings The event that is triggered when registering links that should render on the
 * Settings page in the control panel, when admin changes are disallowed.
 *
 * @see RegisterCpSettings
 */
class RegisterReadonlyCpSettings
{
    public function __construct(
        /** @var array $settings The registered control panel settings */
        public array $settings = []
    ) {}
}
