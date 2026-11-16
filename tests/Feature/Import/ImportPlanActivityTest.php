<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Activity\ActivityTimelinePresenter;
use CraftCms\Cms\Activity\Data\ActivityActor;
use CraftCms\Cms\Activity\EventTypes\ImportPlanCreated;
use CraftCms\Cms\Activity\EventTypes\ImportPlanDeleted;
use CraftCms\Cms\Activity\EventTypes\ImportPlanRan;
use CraftCms\Cms\Activity\EventTypes\ImportPlanUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\Import;
use CraftCms\Cms\Import\ImportPlans;
use CraftCms\Cms\Support\Facades\Activities;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\SystemMessage\Import\SystemMessageImporter;
use CraftCms\Cms\User\Models\User as UserModel;
use Illuminate\Support\Facades\Bus;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->originalRoot = Aliases::get('@root');
    Aliases::set('@root', dirname(__DIR__, 3));

    $this->user = UserModel::factory()->createElement(['fullName' => 'Ada Lovelace']);
    actingAs($this->user);

    $this->importPlan = new ImportPlanData(['editable' => true])
        ->name('Guides')
        ->handle('guides')
        ->steps([new SystemMessageImporter(['source' => 'tests/Fixtures/Import/system-messages.json'])]);
});

afterEach(function () {
    Aliases::set('@root', $this->originalRoot);
});

it('records who created, saved and deleted an import plan', function () {
    $importPlans = app(ImportPlans::class);
    expect($importPlans->saveImportPlan($this->importPlan))->toBeTrue();
    $saved = $importPlans->getImportPlanByUid($this->importPlan->uid)->name('Guides v2');
    expect($importPlans->saveImportPlan($saved))->toBeTrue();
    $importPlans->deleteImportPlan($saved);

    $events = ActivityEvent::query()->oldest('id')->get();

    expect($events->pluck('eventType')->all())->toBe([ImportPlanCreated::class, ImportPlanUpdated::class, ImportPlanDeleted::class])
        ->and($events->pluck('subjectId')->unique()->all())->toBe([$this->importPlan->uid])
        ->and($events->pluck('actorId')->unique()->all())->toBe([$this->user->id])
        ->and($events->map(fn (ActivityEvent $event) => (string) Activities::format($event))->all())->toBe([
            'Created the Guides import plan',
            'Saved the Guides v2 import plan',
            'Deleted the Guides v2 import plan',
        ]);
});

it('credits whoever started a run with running the plan', function () {
    expect(app(ImportPlans::class)->saveImportPlan($this->importPlan))->toBeTrue();
    Bus::fake();

    app(Import::class)->dispatchImport($this->importPlan);

    $ran = ActivityEvent::query()->eventTypes(ImportPlanRan::class)->sole();

    expect($ran->actorType)->toBe(ActivityActor::TYPE_USER)
        ->and($ran->actorId)->toBe($this->user->id)
        ->and((string) Activities::format($ran))->toBe('Ran the Guides import plan');
});

it('links what a run did to its import plan, for those who can view import plans', function () {
    expect(app(ImportPlans::class)->saveImportPlan($this->importPlan))->toBeTrue();
    Bus::fake();
    app(Import::class)->dispatchImport($this->importPlan);
    $ran = ActivityEvent::query()->eventTypes(ImportPlanRan::class)->sole();

    $operation = fn (bool $canView) => app(ActivityTimelinePresenter::class)->events(
        collect([$ran]),
        UserModel::factory()->withPermissions($canView ? ['accessCp', 'viewImportPlans'] : ['accessCp'])->createElement(),
    )->sole()['operation'];

    expect($operation(true))->toBe(['label' => 'Guides', 'url' => Url::cpUrl('import/guides')])
        ->and($operation(false))->toBe(['label' => 'Guides', 'url' => null]);
});
