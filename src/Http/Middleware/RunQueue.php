<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Http\Middleware\Concerns\InjectsActionRequest;
use CraftCms\Cms\Queue\JobProgress;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Injects JavaScript into HTML responses to trigger the queue runner.
 *
 * @since 6.0.0
 */
readonly class RunQueue
{
    use InjectsActionRequest;

    public function __construct(
        private Queue $queue,
        private JobProgress $progressService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->shouldInjectQueueRunner($request, $response)) {
            return $response;
        }

        return $this->injectActionRequest($response, 'queue/run');
    }

    private function shouldInjectQueueRunner(Request $request, Response $response): bool
    {
        if (! Cms::isInstalled()) {
            return false;
        }

        if (! Cms::config()->runQueueAutomatically) {
            return false;
        }

        if (! $this->canInjectActionRequest($request, $response)) {
            return false;
        }

        if ($this->progressService->hasReservedJobs()) {
            return false;
        }

        if ($this->queue->size() === 0) {
            return false;
        }

        return true;
    }
}
