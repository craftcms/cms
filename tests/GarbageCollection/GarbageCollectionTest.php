<?php

use CraftCms\Cms\GarbageCollection\Actions\GarbageCollectionAction;
use CraftCms\Cms\GarbageCollection\GarbageCollection;

arch('All actions extend GarbageCollectionAction')
    ->expect('CraftCms\Cms\GarbageCollection\Actions')
    ->toExtend(GarbageCollectionAction::class);

it('uses every action', function () {
    $actions = collect(File::allFiles(__DIR__ . '/../../src/GarbageCollection/Actions'))
        ->map(fn ($file) => 'CraftCms\\Cms\\GarbageCollection\\Actions\\' . Str::replaceLast('.php', '', $file->getFilename()))
        ->filter(fn ($action) => $action !== GarbageCollectionAction::class);

    $this->mock(GarbageCollection::class)
        ->makePartial()
        ->shouldReceive('runActions')
        ->andReturnUsing(function ($input) use ($actions) {
            foreach ($actions as $action) {
                $found = collect($input)->where(function ($inputAction) use ($action) {
                    return $inputAction === $action || $inputAction[0] === $action;
                })->count();

                expect($found)->toBeGreaterThan(0, "Action {$action} was not run");
            }
        })
        ->once();

    app(GarbageCollection::class)->run(force: true);
});
