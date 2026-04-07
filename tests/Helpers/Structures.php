<?php

declare(strict_types=1);

use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;

function createStructureHierarchy(int $levels = 3): array
{
    if ($levels < 2) {
        throw new InvalidArgumentException('Structure hierarchies require at least 2 levels.');
    }

    $structure = Structure::factory()->create();
    $structure->structureElements()->delete();

    $root = EntryModel::factory()->create();

    $rootNode = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => $root->id,
    ]);
    $rootNode->makeRoot();

    $childrenIds = [];
    $nestedIds = [];
    $branchParent = $rootNode;

    for ($level = 1; $level < $levels; $level++) {
        $entry = EntryModel::factory()->create();
        $node = new StructureElement([
            'structureId' => $structure->id,
            'elementId' => $entry->id,
        ]);
        $node->appendTo($branchParent);

        $branchParent = $node;

        if ($level === 1) {
            $childrenIds[] = $entry->id;

            continue;
        }

        $nestedIds[] = $entry->id;
    }

    $sibling = EntryModel::factory()->create();

    $siblingNode = new StructureElement([
        'structureId' => $structure->id,
        'elementId' => $sibling->id,
    ]);
    $siblingNode->appendTo($rootNode);

    $childrenIds[] = $sibling->id;

    $root = structuredEntry($root->id, $structure->id);
    $children = array_map(
        fn (int $entryId): EntryElement => structuredEntry($entryId, $structure->id),
        $childrenIds,
    );
    $nested = array_map(
        fn (int $entryId): EntryElement => structuredEntry($entryId, $structure->id),
        $nestedIds,
    );

    return [
        'structure' => $structure,
        'root' => $root,
        'children' => $children,
        'nested' => $nested,
        'elements' => [$root, ...$children, ...$nested],
    ];
}

function structuredEntry(int $entryId, int $structureId): EntryElement
{
    /** @var EntryElement $entry */
    $entry = entryQuery()
        ->id($entryId)
        ->structureId($structureId)
        ->one();

    return $entry;
}
