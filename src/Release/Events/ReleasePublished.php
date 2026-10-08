<?php

declare(strict_types=1);

namespace CraftCms\Cms\Release\Events;

use CraftCms\Cms\Release\Models\Release;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * @since 6.0.0
 */
class ReleasePublished implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public Release $release) {}
}
