<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Enums\ImportRunStatus;
use CraftCms\Cms\Import\Enums\ImportRunTrigger;
use CraftCms\Cms\Import\Events\ImportDispatching;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Import\ImportRuns;
use CraftCms\Cms\Import\Models\ImportRun;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    Bus::fake();

    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    $this->importPlan = new ImportPlanData(['editable' => true])
        ->name('Guides')
        ->handle('guides')
        ->steps([new SystemMessageImporter(['uid' => 'step-1', 'source' => 'tests/Fixtures/Import/system-messages.json'])]);
    app(ImportPlans::class)->saveImportPlan($this->importPlan);

    $this->runs = app(ImportRuns::class);
    $this->latest = fn (): ImportRun => $this->runs->latest($this->importPlan);
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('records who started a run, and how', function () {
    $user = UserModel::factory()->createElement();
    actingAs($user);

    app(Import::class)->dispatchImport($this->importPlan, trigger: ImportRunTrigger::ControlPanel);

    expect(($this->latest)())
        ->status->toBe(ImportRunStatus::Pending)
        ->userId->toBe($user->id)
        ->trigger->toBe(ImportRunTrigger::ControlPanel)
        ->planUid->toBe($this->importPlan->uid)
        ->and(array_keys(($this->latest)()->steps))->toBe(['step-1']);
});

it('forgets a run whose dispatch was canceled', function () {
    Event::listen(fn (ImportDispatching $event) => $event->isValid = false);

    app(Import::class)->dispatchImport($this->importPlan);

    expect(ImportRun::query()->count())->toBe(0);
});

it('keeps the first failures of a step run and counts the rest', function () {
    $this->runs->start($this->importPlan, 'run-id', null, null);
    $failure = fn (int $item) => ['item' => $item, 'message' => 'Nope.', 'elementId' => null, 'problems' => [], 'data' => ['key' => $item]];

    $this->runs->recordItems('run-id', 'step-1', 60, 150, ['failed' => 60], array_map($failure, range(1, 60)));
    $this->runs->recordItems('run-id', 'step-1', 150, 150, ['failed' => 90], array_map($failure, range(61, 150)));

    $step = ($this->latest)()->steps['step-1'];

    expect($step['counts']['failed'])->toBe(150)
        ->and($step['failures'])->toHaveCount(ImportRuns::MAX_FAILURES_PER_STEP)
        ->and(end($step['failures'])['item'])->toBe(ImportRuns::MAX_FAILURES_PER_STEP);
});

it('keeps only the latest runs of each plan', function () {
    $other = new ImportPlanData(['editable' => true])->name('Other')->handle('other')
        ->steps([new SystemMessageImporter(['source' => 'tests/Fixtures/Import/system-messages.json'])]);
    app(ImportPlans::class)->saveImportPlan($other);
    $this->runs->start($other, 'other-run', null, null);

    foreach (range(1, ImportRuns::MAX_RUNS_PER_PLAN + 2) as $i) {
        Date::setTestNow(Date::now()->addMinute());
        $this->runs->start($this->importPlan, "run-$i", null, null);
    }

    $uids = ImportRun::query()->where('planUid', $this->importPlan->uid)->pluck('uid');

    expect($uids)->toHaveCount(ImportRuns::MAX_RUNS_PER_PLAN)
        ->not->toContain('run-1', 'run-2')
        ->toContain('run-27')
        ->and(ImportRun::query()->where('uid', 'other-run')->exists())->toBeTrue();
});

it('shows a run that let go of its plan without finishing as failed, and fails it for good once the plan runs again', function () {
    $import = app(Import::class);
    $import->dispatchImport($this->importPlan);
    $first = ($this->latest)()->uid;
    $this->runs->stepStarted($first, 'step-1');

    expect($import->runState($this->importPlan)['status'])->toBe('running');

    $import->unlock($this->importPlan);

    expect($import->runState($this->importPlan))
        ->status->toBe('failed')
        ->and($import->runState($this->importPlan)['steps']['step-1']['status'])->toBe('failed');

    $import->dispatchImport($this->importPlan);

    expect(ImportRun::query()->where('uid', $first)->sole())
        ->status->toBe(ImportRunStatus::Failed)
        ->dateFinished->not->toBeNull();
});

it('deletes a plan’s runs with it', function () {
    $this->runs->start($this->importPlan, 'run-id', null, null);

    app(ImportPlans::class)->deleteImportPlan($this->importPlan);

    expect(ImportRun::query()->count())->toBe(0);
});
