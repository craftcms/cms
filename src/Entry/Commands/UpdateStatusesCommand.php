<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Commands\UpdateElementStatusesCommand;
use CraftCms\Cms\Element\Events\ElementResaved;
use CraftCms\Cms\Element\Events\ElementResaving;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Event;
use Override;
use Throwable;

/**
 * Re-saves entries whose stored statuses no longer match their dates, for the deprecated `staticStatuses` config
 * setting.
 *
 * @since 6.0.0
 * @deprecated in 6.0.0. Turn off `staticStatuses` and run Laravel’s scheduler, which runs
 *             {@see UpdateElementStatusesCommand} every minute.
 */
final class UpdateStatusesCommand extends Command implements Isolatable
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:update-statuses';

    #[Override]
    protected $description = 'Updates statically-stored entry statuses. Deprecated along with the `staticStatuses` config setting.';

    #[Override]
    protected $aliases = ['update-statuses', 'update-statuses:index', 'update-statuses/index'];

    #[Override]
    protected $isolated = true;

    public function handle(): int
    {
        Deprecator::log('craft:update-statuses', 'The `craft:update-statuses` command and the `staticStatuses` config setting are deprecated. Turn `staticStatuses` off and run Laravel’s scheduler. Entry queries will get statuses from post and expiry dates, and Craft will clear caches and dispatch `ElementStatusChanged` when those dates pass.');

        $now = Query::prepareDateForDb(now());

        foreach ($this->conditions($now) as $status => $condition) {
            $this->components->task(
                "Updating $status entries",
                function () use ($condition, $status) {
                    $query = Entry::find()
                        ->site('*')
                        ->unique()
                        ->status(null)
                        ->where('entries.status', '!=', $status)
                        ->where($condition);

                    $this->resaveEntries($query);
                },
            );
        }

        return self::SUCCESS;
    }

    public function isolationLockExpiresAt(): DateTimeInterface
    {
        return now()->plus(minutes: 15);
    }

    /**
     * @return array<string, \Closure(Builder): void>
     */
    private function conditions(string $now): array
    {
        return [
            Entry::STATUS_LIVE => function (Builder $query) use ($now) {
                $query->where('entries.postDate', '<=', $now)
                    ->where(function (Builder $query) use ($now) {
                        $query->whereNull('entries.expiryDate')
                            ->orWhere('entries.expiryDate', '>', $now);
                    });
            },
            Entry::STATUS_PENDING => function (Builder $query) use ($now) {
                $query->where('entries.postDate', '>', $now);
            },
            Entry::STATUS_EXPIRED => function (Builder $query) use ($now) {
                $query->whereNotNull('entries.expiryDate')
                    ->where('entries.expiryDate', '<=', $now);
            },
        ];
    }

    /** @param EntryQuery<Entry> $query */
    private function resaveEntries(EntryQuery $query): void
    {
        $count = $query->count();

        $beforeCallback = function (ElementResaving $event) use ($count, $query) {
            if ($event->query !== $query) {
                return;
            }

            $this->output->write("    - [$event->position/$count] Updating entry ({$event->element->id}) ... ");
        };

        $afterCallback = function (ElementResaved $event) use ($query) {
            if ($event->query !== $query) {
                return;
            }

            if ($event->exception instanceof Throwable) {
                $this->output->writeln("error: {$event->exception->getMessage()}");

                return;
            }

            if ($event->element->errors()->isNotEmpty()) {
                $summary = implode(', ', $event->element->errors()->all());
                $this->output->writeln("failed: $summary");

                return;
            }

            $this->output->writeln('done');
        };

        Event::listen(fn (ElementResaving $event) => $beforeCallback($event));
        Event::listen(fn (ElementResaved $event) => $afterCallback($event));

        Elements::resaveElements($query, continueOnError: true, updateSearchIndex: false);
    }
}
