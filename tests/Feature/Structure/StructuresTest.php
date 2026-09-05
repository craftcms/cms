<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementMovedInStructure;
use CraftCms\Cms\Element\Events\ElementMovingInStructure;
use CraftCms\Cms\Structure\Data\Operation;
use CraftCms\Cms\Structure\Events\ElementUpdated;
use CraftCms\Cms\Structure\Events\StructureElementUpdating;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\Structure\Structures;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

it('releases the structure lock on failure and reacquires it on retry', function (string $eventClass) {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $structures = app(Structures::class);
    $transactionLevel = DB::transactionLevel();
    Event::listen($eventClass, function () {
        throw new RuntimeException('Move failed.');
    });

    expect(fn () => $structures->moveAfter($structure->id, $child1, $child2))
        ->toThrow(RuntimeException::class, 'Move failed.');
    expect(DB::transactionLevel())->toBe($transactionLevel);

    expect(structuredEntry($child1->id, $structure->id)->lft)->toBe(2);

    $contender = Cache::lock("structure:{$structure->id}", 30);
    expect($contender->get())->toBeTrue();
    $contender->release();

    Event::forget($eventClass);

    Event::listen(ElementMovingInStructure::class, function () use ($structure) {
        $contender = Cache::lock("structure:{$structure->id}", 30);
        expect($contender->get())->toBeFalse();
    });

    expect($structures->moveAfter($structure->id, $child1, $child2))->toBeTrue();
    expect(DB::transactionLevel())->toBe($transactionLevel);
    expect(structuredEntry($child1->id, $structure->id)->lft)->toBe(4);
})->with([
    'before event' => StructureElementUpdating::class,
    'before hook' => ElementMovingInStructure::class,
    'transaction start event' => TransactionBeginning::class,
    'after hook rollback' => ElementMovedInStructure::class,
]);

it('releases the structure lock when its element lookup fails', function () {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $structures = app(Structures::class);
    $fail = true;
    $transactionLevel = DB::transactionLevel();

    DB::connection()->beforeExecuting(function (string $query, array $bindings) use ($child1, &$fail) {
        if ($fail && str_contains($query, 'structureelements') && in_array($child1->id, $bindings, true)) {
            throw new RuntimeException('Lookup failed.');
        }
    });

    expect(fn () => $structures->moveAfter($structure->id, $child1, $child2))
        ->toThrow(RuntimeException::class, 'Lookup failed.');
    expect(DB::transactionLevel())->toBe($transactionLevel);

    $contender = Cache::lock("structure:{$structure->id}", 30);
    expect($contender->get())->toBeTrue();
    $contender->release();

    $fail = false;

    expect($structures->moveAfter($structure->id, $child1, $child2))->toBeTrue();
    expect(structuredEntry($child1->id, $structure->id)->lft)->toBe(4);
});

it('releases the structure lock when a move is vetoed', function (string $eventClass) {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $transactionLevel = DB::transactionLevel();

    Event::listen($eventClass, function ($event) {
        $event->isValid = false;
    });

    expect(app(Structures::class)->moveAfter($structure->id, $child1, $child2))->toBeFalse();
    expect(DB::transactionLevel())->toBe($transactionLevel);
    expect(structuredEntry($child1->id, $structure->id)->lft)->toBe(2);

    $contender = Cache::lock("structure:{$structure->id}", 30);
    expect($contender->get())->toBeTrue();
    $contender->release();
})->with([StructureElementUpdating::class, ElementMovingInStructure::class]);

it('rolls back a cancelled node operation and releases the structure lock', function () {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $transactionLevel = DB::transactionLevel();

    StructureElement::saving(fn (StructureElement $model) => $model->elementId === $child1->id ? false : null);

    expect(app(Structures::class)->moveAfter($structure->id, $child1, $child2))->toBeFalse();
    expect(DB::transactionLevel())->toBe($transactionLevel);
    expect(structuredEntry($child1->id, $structure->id)->lft)->toBe(2);

    $contender = Cache::lock("structure:{$structure->id}", 30);
    expect($contender->get())->toBeTrue();
    $contender->release();
});

it('holds the structure lock until the transaction commits', function () {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $transactionLevel = DB::transactionLevel();
    $lockHeldDuringMove = false;
    $afterEventHandled = false;

    Event::listen(ElementUpdated::class, function () use ($structure, $transactionLevel, &$afterEventHandled) {
        $afterEventHandled = true;
        expect(DB::transactionLevel())->toBe($transactionLevel);

        $contender = Cache::lock("structure:{$structure->id}", 30);
        expect($contender->get())->toBeTrue();
        $contender->release();
    });

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
    expect($afterEventHandled)->toBeTrue();

    $lock = Cache::lock("structure:{$structure->id}", 30);

    expect($lock->get())->toBeTrue();

    $lock->release();
});

it('keeps the outer structure lock after nested moves', function (string $outcome) {
    [
        'structure' => $structure,
        'children' => [$child1, $child2],
    ] = createStructureHierarchy();

    $nestedMoveHandled = false;
    $transactionLevel = DB::transactionLevel();

    Event::listen(ElementMovingInStructure::class, function (ElementMovingInStructure $event) use ($child2, $outcome) {
        if ($event->element->id !== $child2->id) {
            return;
        }

        if ($outcome === 'exception') {
            throw new RuntimeException('Nested move failed.');
        }

        $event->isValid = $outcome !== 'veto';
    });

    Event::listen(ElementMovedInStructure::class, function (ElementMovedInStructure $event) use ($structure, $child1, $child2, $outcome, &$nestedMoveHandled) {
        if ($event->structureId !== $structure->id || $nestedMoveHandled) {
            return;
        }

        $nestedMoveHandled = true;

        $transactionLevel = DB::transactionLevel();

        if ($outcome === 'exception') {
            expect(fn () => app(Structures::class)->moveAfter($structure->id, $child2, $child1))
                ->toThrow(RuntimeException::class, 'Nested move failed.');
        } else {
            expect(app(Structures::class)->moveAfter($structure->id, $child2, $child1))->toBe($outcome === 'success');
        }

        expect(DB::transactionLevel())->toBe($transactionLevel);

        $contender = Cache::lock("structure:{$structure->id}", 30);
        expect($contender->get())->toBeFalse();
    });

    expect(app(Structures::class)->moveAfter($structure->id, $child1, $child2))->toBeTrue();
    expect($nestedMoveHandled)->toBeTrue();
    expect(DB::transactionLevel())->toBe($transactionLevel);

    $contender = Cache::lock("structure:{$structure->id}", 30);
    expect($contender->get())->toBeTrue();
    $contender->release();
})->with(['success', 'veto', 'exception']);

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
