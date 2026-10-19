<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Events;

use CraftCms\Cms\Ai\Contracts\TaskInterface;
use CraftCms\Cms\Ai\Data\ResolvedProvider;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a task’s provider returns a response.
 *
 * For streamed prompts, it’s dispatched when the stream ends. Failed requests
 * dispatch {@see TaskFailed} instead.
 *
 * @since 6.0.0
 */
class TaskCompleted
{
    use Dispatchable;

    /**
     * @param  class-string<TaskInterface>  $task
     * @param  object  $response  The `laravel/ai` response.
     * @param  object|null  $usage  The response’s usage, such as `TextUsage` with token counts, or `ImageUsage`.
     * @param  int  $duration  The time the request took, in milliseconds.
     */
    public function __construct(
        public readonly string $task,
        public readonly ResolvedProvider $provider,
        public readonly object $response,
        public readonly ?object $usage,
        public readonly int $duration,
    ) {}
}
