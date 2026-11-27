<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Support\Env;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Runs Craft’s due scheduled tasks from site requests when Laravel’s scheduler isn’t running.
 *
 * @internal
 *
 * @since 6.0.0
 */
class ScheduledWork
{
    /**
     * Craft’s scheduled tasks that requests may run when the scheduler isn’t running, by name, with a method that
     * returns whether the task has work due.
     *
     * @var array<string, array{class-string, string}>
     */
    private const array TASKS = [
        'craft:publish-releases' => [Releases::class, 'hasDue'],
    ];

    private const string REQUEST_RUN_KEY = 'craft:scheduled-work-request-run';

    public function __construct(
        private readonly SchedulerHeartbeat $heartbeat,
    ) {}

    /**
     * Returns whether a request should check for due scheduled tasks, without claiming the check.
     */
    public function requestRunIsDue(): bool
    {
        return ! $this->heartbeat->isRunning() && ! Cache::has(self::REQUEST_RUN_KEY);
    }

    /**
     * Returns whether a request should run due scheduled tasks, claiming the check for the next minute when it should.
     */
    public function claimRequestRun(): bool
    {
        return ! $this->heartbeat->isRunning() && Cache::add(self::REQUEST_RUN_KEY, true, 60);
    }

    /**
     * Returns whether work deferred until after the response is sent runs after the client has the full response,
     * rather than while it waits for the connection to close.
     */
    public function canFinishResponseEarly(): bool
    {
        return function_exists('fastcgi_finish_request')
            || function_exists('litespeed_finish_request')
            || (bool) Env::get('LARAVEL_OCTANE');
    }

    /**
     * Returns whether any of Craft’s scheduled tasks that requests may run has work due.
     */
    public function hasDueWork(): bool
    {
        $names = array_keys(array_filter(
            self::TASKS,
            fn (array $check): bool => app($check[0])->{$check[1]}(),
        ));

        // Resolving the schedule makes the console kernel, so only check the scheduler’s filters once a task has work
        return $names !== [] && array_intersect($names, array_keys($this->dueEvents())) !== [];
    }

    /**
     * Runs Craft’s scheduled tasks that are due, with the same filters and overlap protection as the scheduler.
     */
    public function runDue(): void
    {
        $app = app();

        foreach ($this->dueEvents() as $event) {
            try {
                $event->run($app);
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /**
     * Returns the scheduled events for Craft’s tasks that are due and whose filters pass, by task name.
     *
     * @return array<string, Event>
     */
    private function dueEvents(): array
    {
        $app = app();
        $events = [];

        foreach (app(Schedule::class)->events() as $event) {
            if (
                isset(self::TASKS[$event->description]) &&
                $event->isDue($app) &&
                $event->filtersPass($app)
            ) {
                $events[$event->description] = $event;
            }
        }

        return $events;
    }
}
