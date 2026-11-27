<?php

declare(strict_types=1);

namespace CraftCms\Cms\CriticalNotification\Checks;

use CraftCms\Cms\CriticalNotification\CriticalCheck;
use CraftCms\Cms\CriticalNotification\CriticalNotification;

use function CraftCms\Cms\t;

/**
 * Sends a notification when debug mode is on in production, where it shows error details to visitors.
 *
 * @since 6.0.0
 */
class DebugInProductionCheck implements CriticalCheck
{
    public function key(): string
    {
        return 'debug-in-production';
    }

    public function run(): ?CriticalNotification
    {
        if (! config('app.debug') || ! app()->environment('production')) {
            return null;
        }

        return new CriticalNotification(
            key: $this->key(),
            title: t('Debug mode is on in production'),
            message: t('Visitors can see error details, including stack traces and environment information. Set `APP_DEBUG=false` in production.'),
        );
    }
}
