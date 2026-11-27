<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Middleware;

use Closure;
use CraftCms\Cms\Console\ScheduledWork;
use CraftCms\Cms\Http\Middleware\Concerns\InjectsActionRequest;
use CraftCms\Cms\Support\Env;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use function Illuminate\Support\defer;

/**
 * Runs Craft’s due scheduled tasks from site requests when Laravel’s scheduler isn’t running.
 *
 * Where the server can end the response before the request finishes, the tasks run after the response is sent.
 * Elsewhere, HTML responses request the `scheduled-work/run` action in the background instead, so visitors don’t
 * wait for them.
 *
 * @since 6.0.0
 */
readonly class RunScheduledWork
{
    use InjectsActionRequest;

    public function __construct(
        private ScheduledWork $scheduledWork,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (! $this->scheduledWork->claimCheck()) {
            return $response;
        }

        if ($this->canFinishResponseEarly()) {
            defer(fn () => $this->scheduledWork->runDue())->always();

            return $response;
        }

        if ($this->canInjectActionRequest($request, $response) && $this->scheduledWork->hasDueWork()) {
            return $this->injectActionRequest($response, 'scheduled-work/run');
        }

        return $response;
    }

    /**
     * Returns whether work deferred until after the response is sent runs after the client has the full response,
     * rather than while it waits for the connection to close.
     */
    private function canFinishResponseEarly(): bool
    {
        return function_exists('fastcgi_finish_request')
            || function_exists('litespeed_finish_request')
            || (bool) Env::get('LARAVEL_OCTANE');
    }
}
