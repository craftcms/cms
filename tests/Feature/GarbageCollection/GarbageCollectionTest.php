<?php

declare(strict_types=1);
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\ElementCaches;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedDraftsAndRevisions;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedFieldLayouts;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedNestedElements;
use CraftCms\Cms\GarbageCollection\Actions\DeletePartialElements;
use CraftCms\Cms\GarbageCollection\Actions\GarbageCollectionAction;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\GarbageCollection\Jobs\RunGarbageCollection;
use CraftCms\Cms\Queue\Enums\JobStatus;
use CraftCms\Cms\Queue\Models\JobProgress;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Lottery;
use Symfony\Component\Console\Output\NullOutput;

arch('All actions extend GarbageCollectionAction')
    ->expect('CraftCms\Cms\GarbageCollection\Actions')
    ->toExtend(GarbageCollectionAction::class);

it('is not a singleton', function () {
    expect(app(GarbageCollection::class))->not()->toBe(app(GarbageCollection::class));
});

it('runs on a lottery', function () {
    Lottery::fix([false, true]);

    $runActionsCalls = 0;
    $garbageCollection = garbageCollectionForTest(function () use (&$runActionsCalls) {
        $runActionsCalls++;
    });

    $garbageCollection->run();
    $garbageCollection->run();

    expect($runActionsCalls)->toBe(1);
});

it('queues garbage collection on a lottery', function () {
    Queue::fake();
    Lottery::fix([false, true]);

    $garbageCollection = app(GarbageCollection::class);
    $garbageCollection->queue();
    $garbageCollection->queue();

    Queue::assertPushed(RunGarbageCollection::class, 1);
});

it('uses every action at least once', function () {
    $actions = collect(File::allFiles(__DIR__.'/../../../src/GarbageCollection/Actions'))
        ->map(fn ($file) => 'CraftCms\\Cms\\GarbageCollection\\Actions\\'.Str::replaceLast('.php', '',
            $file->getFilename()))
        ->filter(fn ($action) => $action !== GarbageCollectionAction::class);

    runGarbageCollectionForTest(function (array $input) use ($actions) {
        foreach ($actions as $action) {
            $found = findActionCall($input, $action)->count();

            expect($found)->toBeGreaterThan(0, "Action {$action} was not run");
        }
    });
});

test('it deletes partial elements', function (string $elementType, string $table) {
    runGarbageCollectionForTest(function (array $input) use ($table, $elementType) {
        $found = findActionCall($input, DeletePartialElements::class)->first(fn ($call) => is_array($call) && $elementType === $call[1]['elementType'] && $table === $call[1]['table']);

        expect($found)->not()->toBeNull("Action DeletePartialElements was not run for {$elementType} on {$table}");
    });
})->with([
    [Address::class, Table::ADDRESSES],
    [Asset::class, Table::ASSETS],
    [ContentBlock::class, Table::CONTENTBLOCKS],
    [Entry::class, Table::ENTRIES],
    [User::class, Table::USERS],
]);

test('it deletes orphaned field layouts', function (string $elementType, string $table) {
    runGarbageCollectionForTest(function (array $input) use ($table, $elementType) {
        $found = findActionCall($input, DeleteOrphanedFieldLayouts::class)->first(fn ($call) => is_array($call) && $elementType === $call[1]['elementType'] && $table === $call[1]['table']);

        expect($found)->not()->toBeNull("Action DeleteOrphanedFieldLayouts was not run for {$elementType} on {$table}");
    });
})->with([
    [Asset::class, Table::VOLUMES],
    [Entry::class, Table::ENTRYTYPES],
]);

test('it deletes orphaned nested elements', function (string $elementType, string $table) {
    runGarbageCollectionForTest(function (array $input) use ($table, $elementType) {
        $found = findActionCall($input, DeleteOrphanedNestedElements::class)->first(fn ($call) => is_array($call) && $elementType === $call[1]['elementType'] && $table === $call[1]['table']);

        expect($found)->not()->toBeNull("Action DeleteOrphanedNestedElements was not run for {$elementType} on {$table}");
    });
})->with([
    [Address::class, Table::ADDRESSES],
    [ContentBlock::class, Table::CONTENTBLOCKS],
    [Entry::class, Table::ENTRIES],
]);

it('calls hard delete', function (string|array $tables) {
    runGarbageCollectionForTest(function (array $input) use ($tables) {
        $found = findActionCall($input, HardDelete::class)->first(fn ($call) => is_array($call) && $tables === $call[1]['tables']);

        expect($found)->not()->toBeNull('Action HardDelete was not run with parameter: '.json_encode($tables));
    });
})->with([
    [[
        Table::ENTRYTYPES,
        Table::FIELDS,
        Table::SECTIONS,
    ]],
    [[
        Table::FIELDLAYOUTS,
        Table::SITES,
    ]],
]);

it('uses null output by default for garbage collection actions', function () {
    $action = app(DeleteOrphanedDraftsAndRevisions::class);
    $output = new ReflectionProperty($action, 'output')->getValue($action);

    expect($output->getOutput())->toBeInstanceOf(NullOutput::class);
});

it('prunes job progress records', function () {
    $jobProgress = JobProgress::create([
        'uid' => 'stale-job-progress',
        'description' => 'Stale job progress',
        'status' => JobStatus::Done,
        'progress' => 100,
        'dateCreated' => now()->subDays(8),
        'dateUpdated' => now()->subDays(8),
    ]);
    $recentJobProgress = JobProgress::create([
        'uid' => 'recent-job-progress',
        'description' => 'Recent job progress',
        'status' => JobStatus::Done,
        'progress' => 100,
        'dateCreated' => now()->subDays(6),
        'dateUpdated' => now()->subDays(6),
    ]);

    runGarbageCollectionForTest(fn (array $actions) => null);

    expect(JobProgress::query()->whereKey($jobProgress->getKey())->exists())->toBeFalse()
        ->and(JobProgress::query()->whereKey($recentJobProgress->getKey())->exists())->toBeTrue();
});

function findActionCall(array $actions, string $action): Collection
{
    return collect($actions)->where(fn ($inputAction) => $inputAction === $action || $inputAction[0] === $action);
}

function runGarbageCollectionForTest(callable $assertActions): void
{
    $runActionsWasCalled = false;
    $garbageCollection = garbageCollectionForTest(function (array $actions) use ($assertActions, &$runActionsWasCalled) {
        $runActionsWasCalled = true;
        $assertActions($actions);
    });

    $garbageCollection->run(force: true);

    expect($runActionsWasCalled)->toBeTrue();
}

function garbageCollectionForTest(callable $assertActions): GarbageCollection
{
    return new class(app(ElementCaches::class), Closure::fromCallable($assertActions)) extends GarbageCollection
    {
        public function __construct(
            ElementCaches $elementCaches,
            private readonly Closure $assertActions,
        ) {
            parent::__construct($elementCaches);
        }

        public function runActions(array $actions): void
        {
            ($this->assertActions)($actions);
        }
    };
}
