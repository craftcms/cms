<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import;

use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Enums\ImportItemOutcome;
use CraftCms\Cms\Import\Enums\ImportRunStatus;
use CraftCms\Cms\Import\Enums\ImportRunTrigger;
use CraftCms\Cms\Import\Importers\BaseImporter;
use CraftCms\Cms\Import\Models\ImportRun;
use CraftCms\Cms\Import\Models\ImportRunItem;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\Translation\Locale;
use DateTimeInterface;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

use function CraftCms\Cms\t;

/**
 * Keeps the record of each import plan's runs: how many items each step run created, updated, left unchanged, skipped or failed, and the items that failed.
 *
 * @phpstan-import-type StepRun from ImportRun
 * @phpstan-import-type Failure from ImportRun
 *
 * @since 6.0.0
 */
#[Singleton]
class ImportRuns
{
    /**
     * How many runs are kept for each import plan.
     */
    public const int MAX_RUNS_PER_PLAN = 25;

    /**
     * How many failures each step run keeps; the rest are only counted.
     */
    public const int MAX_FAILURES_PER_STEP = 100;

    /**
     * Records a new pending run of an import plan's enabled steps, and lets go of the plan's oldest runs past the limit.
     *
     * @param  string  $runId  The run's unique ID.
     */
    public function start(ImportPlanData $importPlan, string $runId, ?int $userId, ?ImportRunTrigger $trigger): ImportRun
    {
        // a new run can only start once the last one has let go of the plan, so any still unfinished never will
        $this->forPlan($importPlan)
            ->whereIn('status', [ImportRunStatus::Pending, ImportRunStatus::Running])
            ->get()
            ->each(fn (ImportRun $run) => $this->finish($run, interrupted: true));

        $run = ImportRun::create([
            'uid' => $runId,
            'planUid' => $importPlan->uid,
            'planHandle' => (string) $importPlan->handle,
            'status' => ImportRunStatus::Pending,
            'progress' => 0,
            'userId' => $userId,
            'trigger' => $trigger,
            'steps' => collect($importPlan->enabledSteps())
                ->mapWithKeys(fn (BaseImporter $step) => [$step->uid => [
                    'label' => $step::displayName(),
                    'status' => ImportRunStatus::Pending->value,
                    'progress' => 0,
                    'total' => null,
                    'source' => ['name' => self::sourceName($step->source), 'size' => null],
                    'counts' => array_fill_keys(array_column(ImportItemOutcome::cases(), 'value'), 0),
                    'failures' => [],
                ]])
                ->all(),
        ]);

        $this->prune($importPlan);

        return $run;
    }

    /**
     * Forgets a run that never got going, e.g. because a listener stopped it from being dispatched.
     */
    public function discard(string $runId): void
    {
        ImportRun::query()->where('uid', $runId)->delete();
    }

    /**
     * Returns the import plan's latest run, if it has one.
     */
    public function latest(ImportPlanData $importPlan): ?ImportRun
    {
        return $this->forPlan($importPlan)->latest('dateCreated')->latest('id')->first();
    }

    /**
     * Returns the runs that haven't finished, newest first.
     *
     * @return Collection<int, ImportRun>
     */
    public function unfinished(): Collection
    {
        return ImportRun::query()
            ->whereIn('status', [ImportRunStatus::Pending, ImportRunStatus::Running])
            ->latest('dateCreated')
            ->latest('id')
            ->get();
    }

    /**
     * Deletes all of an import plan's runs.
     */
    public function deleteForPlan(ImportPlanData $importPlan): void
    {
        $this->forPlan($importPlan)->delete();
    }

    /**
     * Records that a queue worker has started on one of a run's steps.
     */
    public function stepStarted(string $runId, string $stepUid): void
    {
        $this->updateStep($runId, $stepUid, function (array $step, ImportRun $run): array {
            $run->dateStarted ??= Date::now();
            $step['status'] = ImportRunStatus::Running->value;

            return $step;
        });
    }

    /**
     * Records how many items a step run has, and how big its source file was, once it's been read.
     */
    public function recordSource(string $runId, string $stepUid, int $total, ?int $size): void
    {
        $this->updateStep($runId, $stepUid, function (array $step) use ($total, $size): array {
            $step['total'] = $total;
            $step['source']['size'] = $size;

            return $step;
        });
    }

    /**
     * Adds what happened to a batch of a step run's items, and records how far through its items the step has got.
     *
     * @param  int  $done  How many of the step's items have been processed.
     * @param  int  $total  How many items the step has.
     * @param  array<value-of<ImportItemOutcome>, int>  $counts  How many of the batch's items came to each outcome.
     * @param  list<Failure>  $failures  The batch's failed items, in order.
     * @param  list<array{item: int, outcome: ImportItemOutcome, elementId: int|null}>  $items  What each of the batch's items came to.
     */
    public function recordItems(string $runId, string $stepUid, int $done, int $total, array $counts, array $failures = [], array $items = []): void
    {
        $this->updateStep($runId, $stepUid, function (array $step, ImportRun $run) use ($stepUid, $done, $total, $counts, $failures, $items): array {
            ImportRunItem::query()->insert(array_map(fn (array $item): array => [
                'runId' => $run->id,
                'stepUid' => $stepUid,
                'item' => $item['item'],
                'outcome' => $item['outcome']->value,
                'elementId' => $item['elementId'],
            ], $items));

            foreach ($counts as $outcome => $count) {
                $step['counts'][$outcome] = ($step['counts'][$outcome] ?? 0) + $count;
            }

            $room = self::MAX_FAILURES_PER_STEP - count($step['failures']);

            if ($room > 0) {
                array_push($step['failures'], ...array_slice($failures, 0, $room));
            }

            $step['status'] = ImportRunStatus::Running->value;
            $step['total'] = $total;
            $step['progress'] = $total > 0 ? (int) floor(min(1, $done / $total) * 100) : 100;

            return $step;
        });
    }

    /**
     * Records that one of a run's steps has finished, failed if any of its items or jobs did.
     */
    public function stepFinished(string $runId, string $stepUid, bool $jobsFailed): void
    {
        $this->updateStep($runId, $stepUid, function (array $step) use ($jobsFailed): array {
            $step['status'] = ($jobsFailed || $step['counts']['failed'] > 0 ? ImportRunStatus::Failed : ImportRunStatus::Successful)->value;
            $step['progress'] = 100;

            return $step;
        });
    }

    /**
     * Records that a run has finished, and returns whether any of its steps failed.
     */
    public function runFinished(string $runId): bool
    {
        return $this->update($runId, fn (ImportRun $run) => $this->finish($run)) === ImportRunStatus::Failed;
    }

    /**
     * Returns whether one of a run's steps has failed, or had an item fail.
     */
    public function stepFailed(string $runId, string $stepUid): bool
    {
        $step = ImportRun::query()->where('uid', $runId)->first()?->steps[$stepUid] ?? null;

        return $step !== null && ($step['status'] === ImportRunStatus::Failed->value || $step['counts']['failed'] > 0);
    }

    /**
     * Returns a run as the control panel shows it.
     *
     * @param  bool  $interrupted  Whether the run let go of its plan without finishing, so is shown as failed.
     * @param  bool  $withFailures  Whether to include each step run's failures.
     * @return array<string, mixed>
     */
    public function toArray(ImportRun $run, bool $interrupted = false, bool $withFailures = true): array
    {
        $steps = $run->steps;
        $status = $run->status;

        if ($interrupted && ! $status->isFinished()) {
            $status = ImportRunStatus::Failed;
            $steps = array_map(self::interruptStep(...), $steps);
        }

        return [
            'id' => $run->uid,
            'url' => Url::cpUrl("import/activity/$run->uid"),
            'status' => $status->value,
            'progress' => $run->progress,
            'trigger' => $run->trigger?->value,
            'user' => $run->user?->username,
            'dateCreated' => $run->dateCreated->toIso8601String(),
            'started' => self::started($run->dateCreated),
            'dateStarted' => $run->dateStarted?->toIso8601String(),
            'dateFinished' => $run->dateFinished?->toIso8601String(),
            'steps' => array_map(fn (array $step): array => [
                'label' => $step['label'],
                'status' => $step['status'],
                'progress' => $step['progress'],
                'total' => $step['total'],
                'source' => $step['source'],
                'counts' => $step['counts'],
                ...($withFailures ? ['failures' => $step['failures']] : []),
            ], $steps),
        ];
    }

    /**
     * When a run started, named as an entry index names a date (today, yesterday, a day this week, or the date), with
     * the time; and in full, with the time zone.
     *
     * @return array{label: string, title: string}
     */
    public static function started(DateTimeInterface $date): array
    {
        $formatter = I18N::getFormatter();
        $date = Date::instance($date)->setTimezone($formatter->timeZone);
        $time = $formatter->asTime($date, Locale::LENGTH_SHORT);
        $day = $date->isToday() ? t('Today') : $formatter->asTimestamp($date, Locale::LENGTH_SHORT);

        return [
            'label' => $day.' '.t('at {time}', ['time' => $time]),
            'title' => $formatter->asDatetime($date, Locale::LENGTH_SHORT, true),
        ];
    }

    /**
     * Marks an unfinished run, and its unfinished steps, as finished; failed if it was interrupted or any step failed.
     */
    private function finish(ImportRun $run, bool $interrupted = false): ImportRunStatus
    {
        $steps = $interrupted ? array_map(self::interruptStep(...), $run->steps) : $run->steps;
        $failed = $interrupted || array_any($steps, fn (array $step) => $step['status'] === ImportRunStatus::Failed->value);

        $run->steps = $steps;
        $run->status = $failed ? ImportRunStatus::Failed : ImportRunStatus::Successful;
        $run->progress = $interrupted ? $run->progress : 100;
        $run->dateFinished = Date::now();
        $run->save();

        return $run->status;
    }

    /**
     * @param  StepRun  $step
     * @return StepRun
     */
    private static function interruptStep(array $step): array
    {
        if ($step['status'] === ImportRunStatus::Running->value) {
            $step['status'] = ImportRunStatus::Failed->value;
        }

        return $step;
    }

    /**
     * @param  callable(StepRun, ImportRun): StepRun  $callback
     */
    private function updateStep(string $runId, string $stepUid, callable $callback): void
    {
        $this->update($runId, function (ImportRun $run) use ($stepUid, $callback): void {
            $steps = $run->steps;

            if (! isset($steps[$stepUid])) {
                return;
            }

            $steps[$stepUid] = $callback($steps[$stepUid], $run);

            $run->steps = $steps;
            $run->status = ImportRunStatus::Running;
            $run->progress = (int) floor(array_sum(array_column($steps, 'progress')) / count($steps));
            $run->save();
        });
    }

    /**
     * Changes a run while holding it, as a run's steps can be recorded by more than one queue worker.
     *
     * @template T
     *
     * @param  callable(ImportRun): T  $callback
     * @return T|null
     */
    private function update(string $runId, callable $callback): mixed
    {
        return DB::transaction(function () use ($runId, $callback) {
            $run = ImportRun::query()->where('uid', $runId)->lockForUpdate()->first();

            return $run !== null ? $callback($run) : null;
        });
    }

    private function prune(ImportPlanData $importPlan): void
    {
        $keep = $this->forPlan($importPlan)
            ->latest('dateCreated')
            ->latest('id')
            ->limit(self::MAX_RUNS_PER_PLAN)
            ->pluck('id');

        $this->forPlan($importPlan)->whereNotIn('id', $keep)->delete();
    }

    /**
     * Returns a query for an import plan's runs.
     *
     * @return Builder<ImportRun>
     */
    public function forPlan(ImportPlanData $importPlan): Builder
    {
        // plans registered in code may not have a UID, so they're known by their handle
        return $importPlan->uid !== null
            ? ImportRun::query()->where('planUid', $importPlan->uid)
            : ImportRun::query()->whereNull('planUid')->where('planHandle', $importPlan->handle);
    }

    private static function sourceName(?string $source): ?string
    {
        if ($source === null || $source === '') {
            return null;
        }

        $path = parse_url($source, PHP_URL_PATH);

        return basename(is_string($path) && $path !== '' ? $path : $source);
    }
}
