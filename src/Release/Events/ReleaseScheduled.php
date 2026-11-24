<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Events;

use CraftCms\Cms\Release\Models\Release;
use DateTimeInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a release is scheduled to publish at `$publishedAt`.
 *
 * Rescheduling a scheduled release unschedules it first, which dispatches {@see ReleaseUnscheduled}. A release
 * whose publication failed can be scheduled again directly, which dispatches this event again.
 *
 * @since 6.0.0
 */
class ReleaseScheduled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Release $release,
        public DateTimeInterface $publishedAt,
    ) {}
}
