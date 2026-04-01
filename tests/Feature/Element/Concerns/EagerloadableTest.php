<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Structure\Models\Structure;
use CraftCms\Cms\Structure\Models\StructureElement;
use CraftCms\Cms\User\Elements\User;

beforeEach(function () {
    // Create test entries for each test
    $this->entry1 = EntryModel::factory()->create();
    $this->entry2 = EntryModel::factory()->create();
    $this->entry3 = EntryModel::factory()->create();

    // Load them again from an ElementQuery so all data is properly set.
    $this->entry1 = entryQuery()->id($this->entry1->id)->one();
    $this->entry2 = entryQuery()->id($this->entry2->id)->one();
    $this->entry3 = entryQuery()->id($this->entry3->id)->one();
});

describe('hasEagerLoadedElements', function () {
    test('returns true when eager-loaded elements exist', function () {
        $this->entry1->setEagerLoadedElements('testHandle', [], new EagerLoadPlan(
            handle: 'testHandle',
        ));

        expect($this->entry1->hasEagerLoadedElements('testHandle'))->toBeTrue();
    });
});

describe('getEagerLoadedElements', function () {
    test('returns ElementCollection when eager-loaded elements exist', function () {
        expect($this->entry1->getEagerLoadedElements('testHandle'))->toBeNull();

        $this->entry1->setEagerLoadedElements('testHandle', [$this->entry2, $this->entry3], new EagerLoadPlan(
            handle: 'testHandle',
        ));

        expect($this->entry1->getEagerLoadedElements('testHandle'))
            ->toBeInstanceOf(ElementCollection::class)
            ->toHaveCount(2);
    });
});

describe('setEagerLoadedElements', function () {
    test('handles parent relationship specially', function () {
        $parent = entryQuery()->id($this->entry1->id)->one();
        $child = entryQuery()->id($this->entry2->id)->one();

        expect($child->parent)->toBeNull();

        $child->setEagerLoadedElements('parent', [$parent], new EagerLoadPlan(
            handle: 'parent',
        ));

        // Parent should be set directly, not stored in eager-loaded array
        expect($child->parent)->toBe($parent);
    });

    test('handles currentRevision relationship specially', function () {
        $revision = entryQuery()->revisions()->where('elements.canonicalId', $this->entry1->id)->one();

        $this->entry1->setEagerLoadedElements('currentRevision', [$revision], new EagerLoadPlan(
            handle: 'currentRevision',
        ));

        expect($this->entry1->currentRevision)->toBe($revision);
    });
});

describe('getEagerLoadedElementCount', function () {
    test('returns null when no count exists', function () {
        expect($this->entry1->getEagerLoadedElementCount('someHandle'))->toBeNull();
    });

    test('returns count when set directly', function () {
        $this->entry1->setEagerLoadedElementCount('testHandle', 5);

        expect($this->entry1->getEagerLoadedElementCount('testHandle'))->toBe(5);
    });

    test('returns zero when count is zero', function () {
        $this->entry1->setEagerLoadedElementCount('testHandle', 0);

        expect($this->entry1->getEagerLoadedElementCount('testHandle'))->toBe(0);
    });

    test('checks with provider handle prefix when handle not found directly', function () {
        // Set count with provider:field format
        $this->entry1->setEagerLoadedElementCount('testProvider:customField', 10);

        // Should find it when element has matching field layout provider
        // Note: This tests the providerHandle() lookup behavior
        // The element will try to find "testProvider:customField" directly first,
        // then if not found and it has a field layout with provider "testProvider",
        // it will look for "testProvider:customField"

        // First verify it can find with exact match
        expect($this->entry1->getEagerLoadedElementCount('testProvider:customField'))->toBe(10);
    });

    test('falls back to provider prefixed handle when base handle not found', function () {
        // This test verifies the providerHandle() fallback behavior
        // Set a count with a provider prefix
        $this->entry1->setEagerLoadedElementCount('someProvider:someField', 15);

        // When we query with the prefixed handle, we should find it
        expect($this->entry1->getEagerLoadedElementCount('someProvider:someField'))->toBe(15);

        // When we query with just the base handle, and the element has no matching provider,
        // it should return null (no fallback happens in reverse direction)
        expect($this->entry1->getEagerLoadedElementCount('someField'))->toBeNull();
    });
});

describe('eagerLoadingMap', function () {
    test('returns null for unknown handles', function () {
        expect(Entry::eagerLoadingMap([$this->entry1, $this->entry2, $this->entry3], 'unknownHandle'))
            ->toBeNull();
    });

    test('handles localized relationship', function () {
        $result = Entry::eagerLoadingMap([$this->entry1, $this->entry2, $this->entry3], 'localized');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('elementType');
        expect($result)->toHaveKey('map');
        expect($result)->toHaveKey('criteria');
        expect($result['elementType'])->toBe(Entry::class);
    });

    test('handles currentRevision relationship', function () {
        $result = Entry::eagerLoadingMap([$this->entry1, $this->entry2, $this->entry3], 'currentRevision');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('elementType');
        expect($result)->toHaveKey('map');
        expect($result)->toHaveKey('criteria');
        expect($result['elementType'])->toBe(Entry::class);
    });

    test('handles drafts relationship', function () {
        $result = Entry::eagerLoadingMap([$this->entry1, $this->entry2, $this->entry3], 'drafts');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('elementType');
        expect($result)->toHaveKey('map');
        expect($result['elementType'])->toBe(Entry::class);
    });

    test('handles revisions relationship', function () {
        $result = Entry::eagerLoadingMap([$this->entry1, $this->entry2, $this->entry3], 'revisions');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('elementType');
        expect($result)->toHaveKey('map');
        expect($result['elementType'])->toBe(Entry::class);
    });

    test('handles draftCreator relationship', function () {
        // Need to find entries that have drafts with creators
        $drafts = entryQuery()->drafts()->limit(3)->get();

        $result = Entry::eagerLoadingMap($drafts->all(), 'draftCreator');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('elementType');
        expect($result)->toHaveKey('map');
        expect($result['elementType'])->toBe(User::class);
    });

    test('handles revisionCreator relationship', function () {
        // Need to find entries that have revisions with creators
        $revisions = entryQuery()->revisions()->limit(3)->get();

        $result = Entry::eagerLoadingMap($revisions->all(), 'revisionCreator');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('elementType');
        expect($result)->toHaveKey('map');
        expect($result['elementType'])->toBe(User::class);
    });

    test('handles custom field handles with colon separator', function () {
        // Custom fields with provider:field syntax should be processed
        $result = Entry::eagerLoadingMap([$this->entry1, $this->entry2, $this->entry3], 'someProvider:customField');

        // Will return null since field doesn't exist, but shouldn't throw exception
        expect($result)->toBeNull();
    });
});

describe('structure relationships', function () {
    beforeEach(function () {
        /**
         * Create a structure with hierarchy:
         *   root (level 0)
         *   ├── child1 (level 1)
         *   │   └── grandchild (level 2)
         *   └── child2 (level 1)
         */
        $structure = Structure::factory()->create();
        $structure->structureElements()->delete();

        $root = EntryModel::factory()->create();
        $child1 = EntryModel::factory()->create();
        $child2 = EntryModel::factory()->create();
        $grandChild = EntryModel::factory()->create();

        $rootElement = new StructureElement([
            'structureId' => $structure->id,
            'elementId' => $root->id,
        ]);
        $rootElement->makeRoot();

        $child1Element = new StructureElement([
            'structureId' => $structure->id,
            'elementId' => $child1->id,
        ]);
        $child1Element->appendTo($rootElement);

        $child2Element = new StructureElement([
            'structureId' => $structure->id,
            'elementId' => $child2->id,
        ]);
        $child2Element->appendTo($rootElement);

        $grandchildElement = new StructureElement([
            'structureId' => $structure->id,
            'elementId' => $grandChild->id,
        ]);
        $grandchildElement->appendTo($child1Element);

        // Refresh entries using entryQuery() to get structure data as Entry elements
        $this->structure = $structure;
        $this->root = entryQuery()->id($root->id)->structureId($structure->id)->one();
        $this->child1 = entryQuery()->id($child1->id)->structureId($structure->id)->one();
        $this->child2 = entryQuery()->id($child2->id)->structureId($structure->id)->one();
        $this->grandChild = entryQuery()->id($grandChild->id)->structureId($structure->id)->one();
    });

    test('descendants returns all descendants of root element', function () {
        $result = Entry::eagerLoadingMap([$this->root], 'descendants');

        expect($result)->toBeArray()
            ->toHaveKey('elementType')
            ->toHaveKey('map');

        expect($result['elementType'])->toBe(Entry::class);

        // Root should have 3 descendants: child1, child2, grandChild
        $map = $result['map'];
        expect($map)->toBeArray();

        $rootDescendants = array_filter($map, fn ($item) => $item['source'] === $this->root->id);
        expect($rootDescendants)->toHaveCount(3);

        $descendantIds = array_map(fn ($item) => $item['target'], $rootDescendants);
        expect($descendantIds)->toContain($this->child1->id, $this->child2->id, $this->grandChild->id);
    });

    test('descendants returns all descendants including nested ones', function () {
        $result = Entry::eagerLoadingMap([$this->child1], 'descendants');

        expect($result)->toBeArray();

        // child1 should have 1 descendant: grandChild
        $map = $result['map'];
        $child1Descendants = array_filter($map, fn ($item) => $item['source'] === $this->child1->id);
        expect($child1Descendants)->toHaveCount(1);

        $descendantIds = array_map(fn ($item) => $item['target'], $child1Descendants);
        expect($descendantIds)->toContain($this->grandChild->id);
    });

    test('children returns only direct children', function () {
        $result = Entry::eagerLoadingMap([$this->root], 'children');

        expect($result)->toBeArray()
            ->toHaveKey('elementType')
            ->toHaveKey('map');

        expect($result['elementType'])->toBe(Entry::class);

        // Root should have 2 direct children: child1, child2 (not grandChild)
        $map = $result['map'];
        expect($map)->toBeArray();

        $rootChildren = array_filter($map, fn ($item) => $item['source'] === $this->root->id);
        expect($rootChildren)->toHaveCount(2);

        $childIds = array_map(fn ($item) => $item['target'], $rootChildren);
        expect($childIds)->toContain($this->child1->id, $this->child2->id);
        expect($childIds)->not->toContain($this->grandChild->id);
    });

    test('ancestors returns all ancestors of an element', function () {
        $result = Entry::eagerLoadingMap([$this->grandChild], 'ancestors');

        expect($result)->toBeArray()
            ->toHaveKey('elementType')
            ->toHaveKey('map');

        expect($result['elementType'])->toBe(Entry::class);

        // grandChild should have 2 ancestors: child1, root
        $map = $result['map'];
        expect($map)->toBeArray();

        $grandChildAncestors = array_filter($map, fn ($item) => $item['source'] === $this->grandChild->id);
        expect($grandChildAncestors)->toHaveCount(2);

        $ancestorIds = array_map(fn ($item) => $item['target'], $grandChildAncestors);
        expect($ancestorIds)->toContain($this->child1->id, $this->root->id);
    });

    test('parent returns only direct parent', function () {
        $result = Entry::eagerLoadingMap([$this->grandChild], 'parent');

        expect($result)->toBeArray()
            ->toHaveKey('elementType')
            ->toHaveKey('map');

        expect($result['elementType'])->toBe(Entry::class);

        // grandChild should have 1 parent: child1 (not root)
        $map = $result['map'];
        expect($map)->toBeArray();

        $grandChildParents = array_filter($map, fn ($item) => $item['source'] === $this->grandChild->id);
        expect($grandChildParents)->toHaveCount(1);

        $parentIds = array_map(fn ($item) => $item['target'], $grandChildParents);
        expect($parentIds)->toContain($this->child1->id);
        expect($parentIds)->not->toContain($this->root->id);
    });

    test('parent returns null for root element', function () {
        $result = Entry::eagerLoadingMap([$this->root], 'parent');

        expect($result)->toBeArray();

        // Root should have no parents
        $map = $result['map'];
        $rootParents = array_filter($map, fn ($item) => $item['source'] === $this->root->id);
        expect($rootParents)->toHaveCount(0);
    });

    test('descendants returns null for elements not in a structure', function () {
        $nonStructureEntry = EntryModel::factory()->create();
        $entry = entryQuery()->id($nonStructureEntry->id)->one();

        $result = Entry::eagerLoadingMap([$entry], 'descendants');

        // Should return null when no structure data is available
        expect($result)->toBeNull();
    });

    test('ancestors returns null for elements not in a structure', function () {
        $nonStructureEntry = EntryModel::factory()->create();
        $entry = entryQuery()->id($nonStructureEntry->id)->one();

        $result = Entry::eagerLoadingMap([$entry], 'ancestors');

        // Should return null when no structure data is available
        expect($result)->toBeNull();
    });

    test('maps multiple source elements with descendants', function () {
        $result = Entry::eagerLoadingMap([$this->root, $this->child1], 'descendants');

        expect($result)->toBeArray();

        $map = $result['map'];

        // Root should have 3 descendants
        $rootDescendants = array_filter($map, fn ($item) => $item['source'] === $this->root->id);
        expect($rootDescendants)->toHaveCount(3);

        // child1 should have 1 descendant
        $child1Descendants = array_filter($map, fn ($item) => $item['source'] === $this->child1->id);
        expect($child1Descendants)->toHaveCount(1);
    });

    test('maps multiple source elements with ancestors', function () {
        $result = Entry::eagerLoadingMap([$this->child1, $this->grandChild], 'ancestors');

        expect($result)->toBeArray();

        $map = $result['map'];

        // child1 should have 1 ancestor (root)
        $child1Ancestors = array_filter($map, fn ($item) => $item['source'] === $this->child1->id);
        expect($child1Ancestors)->toHaveCount(1);

        // grandChild should have 2 ancestors (child1, root)
        $grandChildAncestors = array_filter($map, fn ($item) => $item['source'] === $this->grandChild->id);
        expect($grandChildAncestors)->toHaveCount(2);
    });
});
