<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementMovedInStructure;
use CraftCms\Cms\Structure\Data\Operation;
use CraftCms\Cms\Structure\Models\StructureElement;
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

it('represents removals as operations', function () {
    [
        'structure' => $structure,
        'children' => [$child],
    ] = createStructureHierarchy();

    $operation = null;

    StructureElement::deleting(function (StructureElement $model) use ($child, &$operation) {
        if ($model->elementId === $child->id) {
            $operation = $model->nestedSetOperation?->type;
        }
    });

    expect(app(Structures::class)->remove($structure->id, $child))->toBeTrue();
    expect($operation)->toBe(Operation::Remove);
});
