<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Events;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use Illuminate\Foundation\Events\Dispatchable;
use Throwable;

/**
 * Dispatched when a task’s request fails after it was sent.
 *
 * For streamed prompts, it’s dispatched when the stream fails while it’s read.
 * It isn’t dispatched when a task is unavailable or a {@see TaskRunning} listener
 * cancels the request, because no request was sent.
 *
 * @since 6.0.0
 */
class TaskFailed
{
    use Dispatchable;

    /**
     * @param  class-string<TaskInterface>  $task
     * @param  int  $duration  The time until the request failed, in milliseconds.
     */
    public function __construct(
        public readonly string $task,
        public readonly ResolvedProvider $provider,
        public readonly Throwable $exception,
        public readonly int $duration,
    ) {}
}
