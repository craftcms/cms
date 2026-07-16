<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementMovedInStructure;
use CraftCms\Cms\Structure\Structures;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('holds the structure lock until the transaction commits', function () {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $transactionLevel = DB::transactionLevel();
    $lockHeldDuringMove = false;

    Event::listen(ElementMovedInStructure::class, function (ElementMovedInStructure $event) use ($structure, $transactionLevel, &$lockHeldDuringMove) {
        if ($event->structureId !== $structure->id) {
            return;
        }

        expect(DB::transactionLevel())->toBeGreaterThan($transactionLevel);

        $contender = Cache::lock("structure:{$structure->id}", 30);
        $lockHeldDuringMove = ! $contender->get();
        $contender->release();
    });

    expect(app(Structures::class)->moveAfter($structure->id, $child1, $child2))->toBeTrue();
    expect($lockHeldDuringMove)->toBeTrue();

    $lock = Cache::lock("structure:{$structure->id}", 30);

    expect($lock->get())->toBeTrue();

    $lock->release();
});

it('reuses the structure lock for nested moves', function () {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $nestedMoveHandled = false;

    Event::listen(ElementMovedInStructure::class, function (ElementMovedInStructure $event) use ($structure, $child1, $child2, &$nestedMoveHandled) {
        if ($event->structureId !== $structure->id || $nestedMoveHandled) {
            return;
        }

        $nestedMoveHandled = true;

        expect(app(Structures::class)->moveAfter($structure->id, $child2, $child1))->toBeTrue();
    });

    expect(app(Structures::class)->moveAfter($structure->id, $child1, $child2))->toBeTrue();
    expect($nestedMoveHandled)->toBeTrue();
});
