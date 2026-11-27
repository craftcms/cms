<?php

declare(strict_types=1);

namespace CraftCms\Cms\Console;

use Closure;
use CraftCms\Cms\Cms;
use Illuminate\Auth\SessionGuard;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Cache;
use Throwable;

use function CraftCms\Cms\craftAuth;
use function CraftCms\Cms\maxPowerCaptain;

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
     * Craft’s scheduled tasks that requests may run when the scheduler isn’t running. Each one’s `when()` filters
     * should pass only when it has work due.
     */
    private const array TASKS = [
        'craft:publish-releases',
    ];

    private const string CHECK_KEY = 'craft:scheduled-work-check';

    private const string RUN_KEY = 'craft:scheduled-work-run';

    public function __construct(
        private readonly SchedulerHeartbeat $heartbeat,
    ) {}

    /**
     * Returns whether this request should check for due tasks, claiming the check for the next minute when it should.
     */
    public function claimCheck(): bool
    {
        return $this->shouldRun() && Cache::add(self::CHECK_KEY, true, 60);
    }

    /**
     * Returns whether this request should run due tasks, claiming the run for the next minute when it should.
     */
    public function claimRun(): bool
    {
        return $this->shouldRun() && Cache::add(self::RUN_KEY, true, 60);
    }

    /**
     * Returns whether any of Craft’s tasks that requests may run has work due.
     */
    public function hasDueWork(): bool
    {
        return $this->dueEvents() !== [];
    }

    /**
     * Runs Craft’s tasks that are due, with the same filters and overlap protection as the scheduler, and without the
     * request’s user, as the scheduler would.
     */
    public function runDue(): void
    {
        $events = $this->dueEvents();

        if ($events === []) {
            return;
        }

        maxPowerCaptain();

        $this->withoutUser(function () use ($events): void {
            foreach ($events as $event) {
                try {
                    $event->run(app());
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        });
    }

    private function shouldRun(): bool
    {
        return Cms::isInstalled()
            && Cms::config()->runScheduledWorkAutomatically
            && ! $this->heartbeat->isRunning();
    }

    /**
     * Returns the scheduled events for Craft’s tasks that are due and whose filters pass.
     *
     * @return Event[]
     */
    private function dueEvents(): array
    {
        $app = app();

        return array_values(array_filter(
            app(Schedule::class)->events(),
            fn (Event $event): bool => in_array($event->description, self::TASKS, true)
                && $event->isDue($app)
                && $event->filtersPass($app),
        ));
    }

    /**
     * Calls a function with no authenticated user, so the request’s user isn’t recorded as the one who did the work.
     *
     * Forgetting the user isn’t enough, because the session guard would load it from the session again.
     */
    private function withoutUser(Closure $callback): void
    {
        $guard = craftAuth();
        $user = $guard->user();

        if ($user === null || ! $guard instanceof SessionGuard) {
            $callback();

            return;
        }

        (function (): void {
            $this->user = null;
            $this->loggedOut = true;
        })->call($guard);

        try {
            $callback();
        } finally {
            $guard->setUser($user);
        }
    }
}
