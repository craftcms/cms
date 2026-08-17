<?php

declare(strict_types=1);

use CraftCms\Cms\Structure\Data\Operation;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
    // Delete the factory created one
    $this->structure->structureElements()->delete();
});

it('cannot create a model directly', function () {
    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('::create is not supported for inserting new nodes.');

    StructureElement::create();
});

it('can create a root node', function () {
    expect(StructureElement::count())->toBe(0);

    $model = new StructureElement([
        'structureId' => $this->structure->id,
    ]);
    $model->makeRoot();

    expect(StructureElement::count())->toBe(1);
    tap($model->refresh(), function (StructureElement $model) {
        expect($model->root)->toBe($model->id);
        expect($model->lft)->toBe(1);
        expect($model->rgt)->toBe(2);
        expect($model->level)->toBe(0);
    });
});

it('can append to a node', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $child = new StructureElement(['structureId' => $this->structure->id]);
    $child->appendTo($root);

    $childsChild = new StructureElement(['structureId' => $this->structure->id]);
    $childsChild->appendTo($child);

    $root->refresh();

    expect($root->lft)->toBe(1);
    expect($root->rgt)->toBe(6);
    expect($root->isRoot())->toBeTrue();
    expect($root->children()->count())->toBe(2);
    expect($root->children(1)->count())->toBe(1);

    expect($child->lft)->toBe(2);
    expect($child->rgt)->toBe(5);
    expect($child->level)->toBe(1);
    expect($child->isLeaf())->toBeFalse();
    expect($child->isChildOf($childsChild))->toBeFalse();
    expect($child->isChildOf($root))->toBeTrue();
    expect($child->parents()->first()?->is($root))->toBeTrue();

    expect($childsChild->lft)->toBe(3);
    expect($childsChild->rgt)->toBe(4);
    expect($childsChild->level)->toBe(2);
    expect($childsChild->isLeaf())->toBeTrue();
    expect($childsChild->isChildOf($child))->toBeTrue();
    expect($childsChild->isChildOf($root))->toBeTrue();
    expect($childsChild->parents()->count())->toBe(2);
    expect($childsChild->parents(1)->count())->toBe(1);

    expect(StructureElement::query()->roots()->count())->toBe(1);
    expect(StructureElement::query()->roots()->first()->is($root))->toBeTrue();

    expect(StructureElement::query()->leaves()->count())->toBe(1);
    expect(StructureElement::query()->leaves()->first()->is($childsChild))->toBeTrue();
});

it('can check siblings', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $child1 = new StructureElement(['structureId' => $this->structure->id]);
    $child1->appendTo($root);

    $root->refresh();

    $child2 = new StructureElement(['structureId' => $this->structure->id]);
    $child2->appendTo($root);

    expect($root->children()->count())->toBe(2);

    expect($child1->isChildOf($child2))->toBeFalse();
    expect($child1->prev()->first())->toBeNull();
    expect($child1->next()->first()?->is($child2))->toBeTrue();

    expect($child2->next()->first())->toBeNull();
    expect($child2->prev()->first()?->is($child1))->toBeTrue();
});

it('clears a failed operation before the next save', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $child = new StructureElement(['structureId' => $this->structure->id]);
    $child->appendTo($root);

    expect(fn () => $child->insertBefore($root))
        ->toThrow(RuntimeException::class, 'Can not move a node when the target node is root.');

    expect($child->save())->toBeTrue();
});

it('rolls back changes when an operation is cancelled', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $child = new StructureElement(['structureId' => $this->structure->id]);

    StructureElement::creating(fn (StructureElement $model) => $model === $child ? false : null);

    expect($child->appendTo($root))->toBeFalse();
    expect($root->refresh()->rgt)->toBe(2);
});

it('rejects overlapping operations on the same node', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $firstChild = new StructureElement(['structureId' => $this->structure->id]);
    $firstChild->appendTo($root);

    $secondChild = new StructureElement(['structureId' => $this->structure->id]);
    $secondChild->appendTo($root);

    $attempted = false;

    StructureElement::saved(function (StructureElement $model) use ($firstChild, $secondChild, &$attempted) {
        if ($attempted || ! $model->is($firstChild)) {
            return;
        }

        $attempted = true;
        $model->insertAfter($secondChild);
    });

    expect(fn () => $firstChild->insertBefore($secondChild))
        ->toThrow(RuntimeException::class, 'A nested set operation is already in progress.');

    expect($firstChild->save())->toBeTrue();
});

it('can repeat an operation after it is rolled back', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $firstChild = new StructureElement(['structureId' => $this->structure->id]);
    $firstChild->appendTo($root);

    $secondChild = new StructureElement(['structureId' => $this->structure->id]);
    $secondChild->appendTo($root);

    expect(fn () => DB::transaction(function () use ($firstChild, $secondChild) {
        $firstChild->insertAfter($secondChild);

        throw new RuntimeException('Roll back the move.');
    }))->toThrow(RuntimeException::class, 'Roll back the move.');

    expect($firstChild->refresh()->lft)->toBe(2);
    expect($secondChild->refresh()->lft)->toBe(4);
    expect($firstChild->insertAfter($secondChild))->toBeTrue();
    expect($firstChild->lft)->toBe(4);
    expect($secondChild->lft)->toBe(2);
});

it('can delete with children', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $child = new StructureElement(['structureId' => $this->structure->id]);
    $child->appendTo($root);

    expect(StructureElement::count())->toBe(2);

    $root->deleteWithChildren();

    expect(StructureElement::count())->toBe(0);
});

it('represents direct deletion as a remove operation', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $child = new StructureElement(['structureId' => $this->structure->id]);
    $child->appendTo($root);

    $operation = null;

    StructureElement::deleting(function (StructureElement $model) use ($child, &$operation) {
        if ($model->is($child)) {
            $operation = $model->nestedSetOperation?->type;
        }
    });

    expect($child->delete())->toBeTrue();
    expect($operation)->toBe(Operation::Remove);
});
