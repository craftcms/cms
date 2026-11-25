<?php

declare(strict_types=1);

namespace CraftCms\Cms\CriticalNotification;

/**
 * A daily check for a problem that should send a critical notification.
 *
 * Register checks with {@see CriticalNotifications::registerCheck()}.
 *
 * @since 6.0.0
 */
interface CriticalCheck
{
    /** The key of the notification {@see run()} returns, used to resolve it once the problem is fixed. */
    public function key(): string;

    /**
     * Returns a notification when there’s a problem, or `null` when there isn’t.
     *
     * Throw an exception when the check can’t tell, so an open notification isn’t resolved by mistake.
     */
    public function run(): ?CriticalNotification;
}
