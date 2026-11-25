<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Activity\Data\ActivitySubject;
use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\EventTypes\ImportPlanRan;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Events\ItemImporting;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\ImportRunOperation;
use CraftCms\Cms\Import\Jobs\ImportPipeline;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\SystemMessage\Models\SystemMessage;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\artisan;

beforeEach(function () {
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    $this->importPlan = new ImportPlanData(['editable' => true])
        ->name('Run Plan')
        ->handle('runPlan')
        ->steps([new SystemMessageImporter(['source' => 'tests/Fixtures/Import/system-messages.json'])]);

    expect(app(ImportPlans::class)->saveImportPlan($this->importPlan))->toBeTrue();
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('queues the import plan given by its handle or UID', function (string $property) {
    Bus::fake();

    artisan('craft:import:run-plan', ['plan' => $this->importPlan->$property])
        ->expectsOutputToContain('Import queued.')
        ->assertSuccessful();

    Bus::assertDispatched(fn (ImportPipeline $pipeline) => $pipeline->importPlan->uid === $this->importPlan->uid);
})->with(['handle', 'uid']);

it('fails for an import plan that doesn’t exist', function () {
    Bus::fake();

    artisan('craft:import:run-plan', ['plan' => 'missingPlan'])->assertFailed();

    Bus::assertNotDispatched(ImportPipeline::class);
});

it('fails for an import plan that is already running', function () {
    Bus::fake();
    app(Import::class)->dispatchImport($this->importPlan);

    artisan('craft:import:run-plan', ['plan' => 'runPlan'])
        ->expectsOutputToContain('This import plan is already running.')
        ->assertFailed();

    Bus::assertDispatchedTimes(ImportPipeline::class, 1);
});

it('runs an import plan that is already running with --force', function () {
    Bus::fake();
    app(Import::class)->dispatchImport($this->importPlan);

    artisan('craft:import:run-plan', ['plan' => 'runPlan', '--force' => true])
        ->expectsOutputToContain('Import queued.')
        ->assertSuccessful();

    Bus::assertDispatchedTimes(ImportPipeline::class, 2);
});

it('runs the import plan in the current process with --sync', function () {
    config(['queue.batching.database' => config('database.default')]);

    artisan('craft:import:run-plan', ['plan' => 'runPlan', '--sync' => true])
        ->expectsOutputToContain('Import finished.')
        ->assertSuccessful();

    expect(SystemMessage::whereIn('key', ['run_plan_first', 'run_plan_second'])->pluck('subject')->sort()->values()->all())
        ->toBe(['First subject', 'Second subject'])
        ->and(app(Import::class)->isRunning($this->importPlan))->toBeFalse();
});

it('makes what a run records part of the run, and only what it changes the import’s doing', function () {
    config(['queue.batching.database' => config('database.default')]);
    $changes = [];
    Event::listen(function (ItemImporting $event) use (&$changes) {
        $changes[] = Activities::record(new ElementUpdated(new ActivitySubject('test', $event->data['key'], 'Test')));
    });

    artisan('craft:import:run-plan', ['plan' => 'runPlan', '--sync' => true])->assertSuccessful();
    $after = Activities::record(new ElementUpdated(new ActivitySubject('test', 'after', 'Test')));
    $ran = ActivityEvent::query()->eventTypes(ImportPlanRan::class)->sole();

    expect($ran->operationId)->toBeString()->not->toBeEmpty()
        ->and($ran->snapshots)->not->toHaveKey('origin')
        ->and($ran->snapshots['operation'])->toBe([
            'type' => ImportRunOperation::class,
            'label' => 'Run Plan',
            'planUid' => $this->importPlan->uid,
        ])
        ->and($changes)->toHaveCount(2)
        ->and(array_map(fn (ActivityEvent $event) => $event->operationId, $changes))->each->toBe($ran->operationId)
        ->and(array_map(fn (ActivityEvent $event) => $event->snapshots['origin'], $changes))->each->toBe('Import')
        ->and($after->operationId)->toBeNull()
        ->and($after->snapshots)->not->toHaveKeys(['origin', 'operation']);
});

it('fails a --sync run when an item couldn’t be imported', function () {
    config(['queue.batching.database' => config('database.default')]);
    Event::listen(function (ItemImporting $event) {
        if ($event->data['key'] === 'run_plan_second') {
            throw new RuntimeException('Item failed.');
        }
    });

    artisan('craft:import:run-plan', ['plan' => 'runPlan', '--sync' => true])
        ->expectsOutputToContain('Import finished with failures.')
        ->assertFailed();

    expect(SystemMessage::where('key', 'run_plan_first')->exists())->toBeTrue();
});
