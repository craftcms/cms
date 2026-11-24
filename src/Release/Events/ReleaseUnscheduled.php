<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Events;

use CraftCms\Cms\Release\Models\Release;
use DateTimeInterface;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a scheduled release goes back to being a draft, either because someone unscheduled it
 * or because its last draft was published early or deleted.
 *
 * `$publishedAt` is the time it had been scheduled for. Publishing a scheduled release doesn’t dispatch
 * this; {@see ReleasePublished} is dispatched instead.
 *
 * @since 6.0.0
 */
class ReleaseUnscheduled implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public Release $release,
        public DateTimeInterface $publishedAt,
    ) {}
}
