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
 * A scheduled release can’t be rescheduled directly: it’s unscheduled first, which dispatches
 * {@see ReleaseUnscheduled}.
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
