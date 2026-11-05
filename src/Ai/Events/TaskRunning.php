<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Events;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched before a task sends a request to its provider.
 *
 * Set `$cancel` to `true` to stop the request, such as when a usage limit is reached.
 * The task then throws an {@see AiUnavailableException}
 * with the `Canceled` reason.
 *
 * @since 6.0.0
 */
class TaskRunning
{
    use Dispatchable;

    public bool $cancel = false;

    /**
     * @param  class-string<TaskInterface>  $task
     * @param  object  $request  The `laravel/ai` agent or pending request being run.
     */
    public function __construct(
        public readonly string $task,
        public readonly ResolvedProvider $provider,
        public readonly object $request,
    ) {}
}
