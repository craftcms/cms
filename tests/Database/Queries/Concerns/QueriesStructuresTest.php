<?php

use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;

test('structureId', function () {
    $structure1 = Structure::factory()->create();
    // Clean structure from factory created elements
    $structure1->structureElements()->delete();

    $structure2 = Structure::factory()->create();
    // Clean structure from factory created elements
    $structure2->structureElements()->delete();

    $entry1 = Entry::factory()->create();
    $entry2 = Entry::factory()->create();

    new StructureElement([
        'structureId' => $structure1->id,
        'elementId' => $entry1->id,
    ])->makeRoot();

    new StructureElement([
        'structureId' => $structure2->id,
        'elementId' => $entry2->id,
    ])->makeRoot();

    expect(entryQuery()->count())->toBe(2);
    expect(entryQuery()->structureId($structure1->id)->level(0)->count())->toBe(1);
    expect(entryQuery()->structureId($structure2->id)->level(0)->count())->toBe(1);
});

test('structure methods', function () {
    $structure = Structure::factory()->create();
    // Clean structure from factory created elements
    $structure->structureElements()->delete();

    $entries = Entry::factory(3)->create();

    foreach ($entries as $index => $entry) {
        $structureElement = new StructureElement([
            'structureId' => $structure->id,
            'elementId' => $entry->id,
        ]);

        if ($index === 0) {
            $structureElement->makeRoot();
            $root = $structureElement;

            continue;
        }

        $structureElement->appendTo($root);
        $lastStructureElement = $structureElement;
    }

    $child = Entry::factory()->create();
    new StructureElement([
        'structureId' => $structure->id,
        'elementId' => $child->id,
    ])->appendTo($lastStructureElement);

    expect(entryQuery()->count())->toBe(4);

    $query = entryQuery()->structureId($structure->id);

    expect($query->clone()->level(0)->count())->toBe(1);
    expect($query->clone()->level('> 0')->count())->toBe(3);
    expect($query->clone()->leaves()->count())->toBe(2);
    expect($query->clone()->nextSiblingOf($entries[1]->id)->count())->toBe(1);
    expect($query->clone()->prevSiblingOf($entries[1]->id)->count())->toBe(0);
    expect($query->clone()->positionedBefore($entries[1]->id)->count())->toBe(1);
    expect($query->clone()->level(1)->positionedAfter($entries[1]->id)->count())->toBe(1);
    expect($query->clone()->level(1)->nextSiblingOf($entries[2]->id)->count())->toBe(0);
    expect($query->clone()->level(1)->prevSiblingOf($entries[2]->id)->count())->toBe(1);
    expect($query->clone()->level(1)->positionedBefore($entries[2]->id)->count())->toBe(1);
    expect($query->clone()->level(1)->positionedAfter($entries[2]->id)->count())->toBe(0);
    expect($query->clone()->level(1)->siblingOf($entries[1]->id)->count())->toBe(1);
    expect($query->clone()->hasDescendants()->count())->toBe(2);
    expect($query->clone()->hasDescendants(false)->count())->toBe(2);
    expect($query->clone()->descendantOf($entries[0]->id)->count())->toBe(3);
    expect($query->clone()->descendantOf($lastStructureElement->elementId)->count())->toBe(1);
    expect($query->clone()->ancestorOf($lastStructureElement->elementId)->count())->toBe(1);
    expect($query->clone()->ancestorOf($entries[1]->id)->count())->toBe(1);
});
