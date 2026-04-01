<?php

declare(strict_types=1);

namespace CraftCms\Cms\Entry\Commands;

use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Events\AfterResaveElement;
use CraftCms\Cms\Element\Events\AfterResaveElements;
use CraftCms\Cms\Element\Events\BeforeResaveElement;
use CraftCms\Cms\Element\Events\BeforeResaveElements;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Support\DateTimeHelper;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Query;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Event;
use Override;
use Throwable;

final class UpdateStatusesCommand extends Command implements Isolatable
{
    use CraftCommand;

    #[Override]
    protected $signature = 'craft:update-statuses';

    #[Override]
    protected $description = 'Updates statically-stored entry statuses.';

    #[Override]
    protected $aliases = ['update-statuses', 'update-statuses:index', 'update-statuses/index'];

    #[Override]
    protected $isolated = true;

    public function handle(): int
    {
        $now = Query::prepareDateForDb(DateTimeHelper::now());

        foreach ($this->conditions($now) as $status => $condition) {
            $this->components->task(
                "Updating $status entries",
                function () use ($condition, $status) {
                    $query = Entry::find()
                        ->site('*')
                        ->unique()
                        ->status(null)
                        ->where('status', '!=', $status)
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
                        $query->whereNull('expiryDate')
                            ->orWhere('expiryDate', '>', $now);
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

    private function resaveEntries(EntryQuery $query): void
    {
        $count = $query->count();

        $beforeCallback = function (BeforeResaveElement $event) use ($count, $query) {
            if ($event->query !== $query) {
                return;
            }

            $this->output->write("    - [$event->position/$count] Updating entry ({$event->element->id}) ... ");
        };

        $afterCallback = function (AfterResaveElement $event) use ($query) {
            if ($event->query !== $query) {
                return;
            }

            if ($event->exception instanceof Throwable) {
                $this->output->writeln("error: {$event->exception->getMessage()}");

                return;
            }

            if ($event->element->errors()->isNotEmpty()) {
                $summary = implode(', ', $event->element->getErrorSummary(true));
                $this->output->writeln("failed: $summary");

                return;
            }

            $this->output->writeln('done');
        };

        Event::listen(fn (BeforeResaveElement $event) => $beforeCallback($event));
        Event::listen(fn (AfterResaveElement $event) => $afterCallback($event));

        $this->resaveQuery($query);
    }

    private function resaveQuery(EntryQuery $query): void
    {
        event(new BeforeResaveElements($query));

        $position = 0;

        foreach ($query->cursor() as $entry) {
            $position++;
            $entry->setScenario(Element::SCENARIO_ESSENTIALS);
            $entry->resaving = true;

            event(new BeforeResaveElement($query, $entry, $position));

            $exception = null;

            try {
                $this->ensureEntryCanBeResaved($entry);
                Elements::saveElement($entry, true, true, false, false, false, true);
            } catch (Throwable $exception) {
                report($exception);
            }

            event(new AfterResaveElement($query, $entry, $position, $exception));
        }

        event(new AfterResaveElements($query));
    }

    private function ensureEntryCanBeResaved(Entry $entry): void
    {
        $label = $entry->getUiLabel();
        $label = $label !== '' ? "$label ($entry->id)" : sprintf('%s %s', $entry::lowerDisplayName(), $entry->id);

        try {
            if (ElementHelper::isRevision($entry)) {
                throw new InvalidElementException($entry, "Skipped resaving $label because it's a revision.");
            }
        } catch (Throwable $exception) {
            throw new InvalidElementException(
                $entry,
                "Skipped resaving $label due to an error obtaining its root element: {$exception->getMessage()}",
                previous: $exception,
            );
        }
    }
}
