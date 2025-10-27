<?php

declare(strict_types=1);

use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;

beforeEach(function () {
    $this->structure = Structure::factory()->create();
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

it('can delete with children', function () {
    $root = new StructureElement(['structureId' => $this->structure->id]);
    $root->makeRoot();

    $child = new StructureElement(['structureId' => $this->structure->id]);
    $child->appendTo($root);

    expect(StructureElement::count())->toBe(2);

    $root->deleteWithChildren();

    expect(StructureElement::count())->toBe(0);
});
