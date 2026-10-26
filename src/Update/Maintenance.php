<?php

declare(strict_types=1);

namespace CraftCms\Cms\Update;

use CraftCms\Cms\ProjectConfig\ProjectConfig;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Foundation\Events\MaintenanceModeDisabled;
use Illuminate\Foundation\Events\MaintenanceModeEnabled;
use Illuminate\Support\Facades\Event;

/**
 * Turns the system’s maintenance mode on and off, as the General settings do.
 *
 * @since 6.0.0
 */
#[Singleton]
readonly class Maintenance
{
    public function __construct(
        private MaintenanceMode $maintenanceMode,
        private ProjectConfig $projectConfig,
    ) {}

    public function isEnabled(): bool
    {
        return $this->maintenanceMode->active();
    }

    /**
     * The number of seconds clients are told to wait before retrying, from the General settings.
     */
    public function retryDuration(): ?int
    {
        $retryDuration = $this->projectConfig->get('system.retryDuration');

        return $retryDuration === null ? null : (int) $retryDuration;
    }

    /**
     * Turns maintenance mode on or off. Turning it on while it’s already on only updates the retry duration.
     */
    public function set(bool $enabled, ?int $retryDuration): void
    {
        $active = $this->maintenanceMode->active();

        if ($enabled) {
            $currentPayload = $active ? $this->maintenanceMode->data() : [];
            $payload = $currentPayload;

            if ($retryDuration === null) {
                unset($payload['retry']);
            } else {
                $payload['retry'] = $retryDuration;
            }

            if ($active && $payload === $currentPayload) {
                return;
            }

            $this->maintenanceMode->activate($payload);

            if (! $active) {
                Event::dispatch(new MaintenanceModeEnabled);
            }

            return;
        }

        if (! $active) {
            return;
        }

        $this->maintenanceMode->deactivate();
        Event::dispatch(new MaintenanceModeDisabled);
    }
}
