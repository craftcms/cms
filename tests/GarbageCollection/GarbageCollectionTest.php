<?php

declare(strict_types=1);
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Elements\ContentBlock;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedFieldLayouts;
use CraftCms\Cms\GarbageCollection\Actions\DeleteOrphanedNestedElements;
use CraftCms\Cms\GarbageCollection\Actions\DeletePartialElements;
use CraftCms\Cms\GarbageCollection\Actions\GarbageCollectionAction;
use CraftCms\Cms\GarbageCollection\Actions\HardDelete;
use CraftCms\Cms\GarbageCollection\GarbageCollection;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Lottery;

arch('All actions extend GarbageCollectionAction')
    ->expect('CraftCms\Cms\GarbageCollection\Actions')
    ->toExtend(GarbageCollectionAction::class);

it('is not a singleton', function () {
    expect(app(GarbageCollection::class))->not()->toBe(app(GarbageCollection::class));
});

it('runs on a lottery', function () {
    Lottery::fix([false, true]);

    $this->mock(GarbageCollection::class)
        ->makePartial()
        ->shouldReceive('runActions')
        ->once();

    app(GarbageCollection::class)->run();
    app(GarbageCollection::class)->run();
});

it('uses every action at least once', function () {
    $actions = collect(File::allFiles(__DIR__.'/../../src/GarbageCollection/Actions'))
        ->map(fn ($file) => 'CraftCms\\Cms\\GarbageCollection\\Actions\\'.Str::replaceLast('.php', '',
            $file->getFilename()))
        ->filter(fn ($action) => $action !== GarbageCollectionAction::class);

    $this->mock(GarbageCollection::class)
        ->makePartial()
        ->shouldReceive('runActions')
        ->andReturnUsing(function ($input) use ($actions) {
            foreach ($actions as $action) {
                $found = findActionCall($input, $action)->count();

                expect($found)->toBeGreaterThan(0, "Action {$action} was not run");
            }
        })
        ->once();

    app(GarbageCollection::class)->run(force: true);
});

test('it deletes partial elements', function (string $elementType, string $table) {
    $this->mock(GarbageCollection::class)
        ->makePartial()
        ->shouldReceive('runActions')
        ->andReturnUsing(function ($input) use ($table, $elementType) {
            $found = findActionCall($input, DeletePartialElements::class)->first(fn ($call) => is_array($call) && $elementType === $call[1]['elementType'] && $table === $call[1]['table']);

            expect($found)->not()->toBeNull("Action DeletePartialElements was not run for {$elementType} on {$table}");
        })
        ->once();

    app(GarbageCollection::class)->run(force: true);
})->with([
    [Address::class, Table::ADDRESSES],
    [Asset::class, Table::ASSETS],
    [ContentBlock::class, Table::CONTENTBLOCKS],
    [Entry::class, Table::ENTRIES],
    [User::class, Table::USERS],
]);

test('it deletes orphaned field layouts', function (string $elementType, string $table) {
    $this->mock(GarbageCollection::class)
        ->makePartial()
        ->shouldReceive('runActions')
        ->andReturnUsing(function ($input) use ($table, $elementType) {
            $found = findActionCall($input, DeleteOrphanedFieldLayouts::class)->first(fn ($call) => is_array($call) && $elementType === $call[1]['elementType'] && $table === $call[1]['table']);

            expect($found)->not()->toBeNull("Action DeleteOrphanedFieldLayouts was not run for {$elementType} on {$table}");
        })
        ->once();

    app(GarbageCollection::class)->run(force: true);
})->with([
    [Asset::class, Table::VOLUMES],
    [Entry::class, Table::ENTRYTYPES],
]);

test('it deletes orphaned nested elements', function (string $elementType, string $table) {
    $this->mock(GarbageCollection::class)
        ->makePartial()
        ->shouldReceive('runActions')
        ->andReturnUsing(function ($input) use ($table, $elementType) {
            $found = findActionCall($input, DeleteOrphanedNestedElements::class)->first(fn ($call) => is_array($call) && $elementType === $call[1]['elementType'] && $table === $call[1]['table']);

            expect($found)->not()->toBeNull("Action DeleteOrphanedNestedElements was not run for {$elementType} on {$table}");
        })
        ->once();

    app(GarbageCollection::class)->run(force: true);
})->with([
    [Address::class, Table::ADDRESSES],
    [ContentBlock::class, Table::CONTENTBLOCKS],
    [Entry::class, Table::ENTRIES],
]);

it('calls hard delete', function (string|array $tables) {
    $this->mock(GarbageCollection::class)
        ->makePartial()
        ->shouldReceive('runActions')
        ->andReturnUsing(function ($input) use ($tables) {
            $found = findActionCall($input, HardDelete::class)->first(fn ($call) => is_array($call) && $tables === $call[1]['tables']);

            expect($found)->not()->toBeNull('Action HardDelete was not run with parameter: '.json_encode($tables));
        })
        ->once();

    app(GarbageCollection::class)->run(force: true);
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

function findActionCall(array $actions, string $action): Collection
{
    return collect($actions)->where(fn ($inputAction) => $inputAction === $action || $inputAction[0] === $action);
}
