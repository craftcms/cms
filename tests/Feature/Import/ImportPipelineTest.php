<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ImportChunkFinished;
use CraftCms\Cms\Import\Events\ImportChunkStarted;
use CraftCms\Cms\Import\Events\ImportFinished;
use CraftCms\Cms\Import\Events\ImportStarted;
use CraftCms\Cms\Import\Events\ImportStepFinished;
use CraftCms\Cms\Import\Events\ImportStepStarted;
use CraftCms\Cms\Import\Events\ItemImported;
use CraftCms\Cms\Import\Events\ItemImporting;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\Jobs\FinishImport;
use CraftCms\Cms\Import\Jobs\Import as ImportJob;
use CraftCms\Cms\Import\Jobs\ImportPipeline;
use CraftCms\Cms\Support\Facades\ImportPlan;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Illuminate\Support\Testing\Fakes\BatchFake;

beforeEach(function () {
    Bus::fake();

    // resolvedFilePath() resolves against @root, which points at the Testbench skeleton in tests
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    $this->filePath = SystemMessageImporter::resolvedFilePath('tests/Fixtures/Import/entries-plain-text.json');

    $this->importPlan = new ImportPlanData([
        'name' => 'Pipeline Plan',
        'handle' => 'pipelinePlan',
        'uid' => 'plan-uid',
        'steps' => [
            new SystemMessageImporter(['uid' => 'step-1', 'file' => 'tests/Fixtures/Import/entries-plain-text.json']),
            new SystemMessageImporter(['uid' => 'step-2', 'file' => 'tests/Fixtures/Import/entries-plain-text.json']),
        ],
    ]);

    $this->step = fn (string $uid) => [
        'name' => "Step {$uid}",
        'uid' => $uid,
        'job' => new ImportJob('plan-uid', $uid, '/path/to/file.json', 'run-id'),
    ];

    $this->batchWithFailures = fn (int $failedJobs) => new BatchFake(
        'batch-id', 'Step', 1, 0, $failedJobs, [], [], CarbonImmutable::now(),
    );

    $this->pendingBatches = function (): array {
        $pendingBatches = [];

        Bus::assertChained([
            Bus::chainedBatch(function (PendingBatch $batch) use (&$pendingBatches) {
                $pendingBatches[0] = $batch;

                return true;
            }),
            Bus::chainedBatch(function (PendingBatch $batch) use (&$pendingBatches) {
                $pendingBatches[1] = $batch;

                return true;
            }),
            FinishImport::class,
        ]);

        return $pendingBatches;
    };

    $this->runJob = function (int $start = 0): void {
        ImportPlan::shouldReceive('getImportPlanByUid')->with('plan-uid')->andReturn($this->importPlan);

        [$job] = new ImportJob('plan-uid', 'step-1', $this->filePath, 'run-id', $start)->withFakeBatch();
        $job->handle();
    };
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('chains a batch per step followed by the finish import job', function () {
    new ImportPipeline([($this->step)('step-1'), ($this->step)('step-2')], $this->importPlan, 'run-id')->handle();

    Bus::assertChained([
        Bus::chainedBatch(fn (PendingBatch $batch) => $batch->name === 'Step step-1' && $batch->allowsFailures()),
        Bus::chainedBatch(fn (PendingBatch $batch) => $batch->name === 'Step step-2' && $batch->allowsFailures()),
        FinishImport::class,
    ]);
});

it('dispatches nothing when there are no steps', function () {
    new ImportPipeline([], $this->importPlan, 'run-id')->handle();

    Bus::assertNothingDispatched();
});

it('flags the run as having failures when a step batch had failed jobs', function () {
    new ImportPipeline([($this->step)('step-1'), ($this->step)('step-2')], $this->importPlan, 'run-id')->handle();
    $pendingBatches = ($this->pendingBatches)();

    $pendingBatches[0]->finallyCallbacks()[0](($this->batchWithFailures)(0));

    expect(Cache::has(FinishImport::hasFailuresCacheKey('run-id')))->toBeFalse();

    $pendingBatches[1]->finallyCallbacks()[0](($this->batchWithFailures)(1));

    expect(Cache::has(FinishImport::hasFailuresCacheKey('run-id')))->toBeTrue();
});

it('fires the import finished event once with failures and clears the run’s failure flag', function () {
    Event::fake([ImportFinished::class]);
    Cache::put(FinishImport::hasFailuresCacheKey('run-id'), true);

    new FinishImport($this->importPlan, 'run-id')->handle();

    Event::assertDispatchedTimes(ImportFinished::class, 1);
    Event::assertDispatched(fn (ImportFinished $event) => $event->importPlan->handle === 'pipelinePlan'
        && count($event->steps) === 2
        && $event->runId === 'run-id'
        && $event->hasFailures);
    expect(Cache::has(FinishImport::hasFailuresCacheKey('run-id')))->toBeFalse();
});

it('fires the import finished event without failures when no step flagged any', function () {
    Event::fake([ImportFinished::class]);

    new FinishImport($this->importPlan, 'run-id')->handle();

    Event::assertDispatched(fn (ImportFinished $event) => $event->hasFailures === false);
});

it('gives the pipeline and every step job the same run ID when dispatching an import', function () {
    app(Import::class)->dispatchImport($this->importPlan);

    Bus::assertDispatched(function (ImportPipeline $pipeline) {
        $jobRunIds = array_map(fn (array $step) => (fn () => $this->runId)->call($step['job']), $pipeline->steps);

        return Str::isUuid($pipeline->runId) && $jobRunIds === [$pipeline->runId, $pipeline->runId];
    });
});

it('passes the run ID to the item importing and imported events', function () {
    Event::fake([ItemImporting::class, ItemImported::class]);

    app(Import::class)->importItem(SystemMessageImporter::create(), [
        'key' => 'my_message',
        'language' => 'en',
        'subject' => 'my subject',
        'body' => 'my body',
    ], [], 'run-id');

    Event::assertDispatched(fn (ItemImporting $event) => $event->runId === 'run-id');
    Event::assertDispatched(fn (ItemImported $event) => $event->runId === 'run-id'
        && $event->importedItem instanceof SystemMessage
        && $event->importedItem->exists
        && $event->importedItem->key === 'my_message');
});

it('flags the run and the step as having failures when an item in an import job fails', function () {
    Event::fake([ImportChunkFinished::class]);
    Event::listen(ItemImporting::class, fn () => throw new Exception('Item failed.'));

    ($this->runJob)();

    expect(Cache::has(FinishImport::hasFailuresCacheKey('run-id')))->toBeTrue()
        ->and(Cache::has(FinishImport::stepHasFailuresCacheKey('run-id', 'step-1')))->toBeTrue()
        ->and(Cache::has(FinishImport::stepHasFailuresCacheKey('run-id', 'step-2')))->toBeFalse();
    Event::assertDispatched(fn (ImportChunkFinished $event) => $event->hasFailures);
});

it('fires the import started event with the plan’s steps and run ID', function () {
    Event::fake([ImportStarted::class]);

    new ImportPipeline([($this->step)('step-1'), ($this->step)('step-2')], $this->importPlan, 'run-id')->handle();

    Event::assertDispatchedTimes(ImportStarted::class, 1);
    Event::assertDispatched(fn (ImportStarted $event) => $event->importPlan === $this->importPlan
        && $event->steps === $this->importPlan->steps
        && $event->runId === 'run-id');
});

it('does not fire the import started event when there are no steps', function () {
    Event::fake([ImportStarted::class]);

    new ImportPipeline([], $this->importPlan, 'run-id')->handle();

    Event::assertNotDispatched(ImportStarted::class);
});

it('fires the step finished event for each step, flagging item and job failures', function () {
    Event::fake([ImportStepFinished::class]);

    new ImportPipeline([($this->step)('step-1'), ($this->step)('step-2')], $this->importPlan, 'run-id')->handle();
    [$firstBatch, $secondBatch] = ($this->pendingBatches)();

    $firstBatch->finallyCallbacks()[0](($this->batchWithFailures)(0));

    Event::assertDispatched(fn (ImportStepFinished $event) => $event->step->uid === 'step-1'
        && $event->importPlan->handle === 'pipelinePlan'
        && $event->runId === 'run-id'
        && $event->hasFailures === false);

    Cache::put(FinishImport::stepHasFailuresCacheKey('run-id', 'step-2'), true);
    $secondBatch->finallyCallbacks()[0](($this->batchWithFailures)(0));

    Event::assertDispatched(fn (ImportStepFinished $event) => $event->step->uid === 'step-2'
        && $event->hasFailures);
    expect(Cache::has(FinishImport::stepHasFailuresCacheKey('run-id', 'step-2')))->toBeFalse();

    $firstBatch->finallyCallbacks()[0](($this->batchWithFailures)(1));

    Event::assertDispatched(fn (ImportStepFinished $event) => $event->step->uid === 'step-1'
        && $event->hasFailures);
});

it('fires the step started and chunk events from the first chunk’s import job', function () {
    Event::fake([ImportStepStarted::class, ImportChunkStarted::class, ImportChunkFinished::class]);
    Event::listen(ItemImporting::class, function (ItemImporting $event) {
        $event->isValid = false;
    });

    ($this->runJob)();

    Event::assertDispatched(fn (ImportStepStarted $event) => $event->step->uid === 'step-1'
        && $event->importPlan === $this->importPlan
        && $event->runId === 'run-id');
    Event::assertDispatched(fn (ImportChunkStarted $event) => $event->step->uid === 'step-1'
        && $event->offset === 0
        && $event->limit === 5);
    Event::assertDispatched(fn (ImportChunkFinished $event) => $event->offset === 0
        && $event->count === 3
        && $event->hasFailures === false);
});

it('fires only the chunk events from a later chunk’s import job', function () {
    Event::fake([ImportStepStarted::class, ImportChunkStarted::class, ImportChunkFinished::class]);
    Event::listen(ItemImporting::class, function (ItemImporting $event) {
        $event->isValid = false;
    });

    ($this->runJob)(1);

    Event::assertNotDispatched(ImportStepStarted::class);
    Event::assertDispatched(fn (ImportChunkStarted $event) => $event->offset === 1);
    Event::assertDispatched(fn (ImportChunkFinished $event) => $event->offset === 1
        && $event->count === 2);
});
