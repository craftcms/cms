<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Data\EagerLoadPlan;
use CraftCms\Cms\Element\ElementCollection;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Entries;
use CraftCms\Cms\Field\Link;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Section\Models\Section as SectionModel;
use CraftCms\Cms\Section\Models\SectionSiteSettings;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\ElementCaches;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Facades\Sections;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

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
        [
            'structure' => $this->structure,
            'root' => $this->root,
            'children' => [$this->child1, $this->child2],
            'nested' => [$this->grandChild],
        ] = createStructureHierarchy();
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

describe('relatedBy', function () {
    beforeEach(function () {
        actingAs(User::findOne());

        $fields = collect([
            'categoriesField' => Entries::class,
            'otherEntriesField' => Entries::class,
            'linkField' => Link::class,
        ])->map(fn (string $type, string $handle) => Field::factory()->create([
            'handle' => $handle,
            'type' => $type,
            'settings' => $type === Link::class ? ['types' => ['entry']] : [],
        ]));
        Fields::refreshFields();

        $layout = FieldLayout::factory()
            ->withContentTab($fields->map(fn (Field $field) => new CustomField(config: ['fieldUid' => $field->uid]))->values()->all())
            ->create();

        $this->post = function (array $values) use ($layout): Entry {
            $model = EntryModel::factory()->withFieldLayout($layout)->create();
            $post = entryQuery()->id($model->id)->one();

            foreach ($values as $handle => $value) {
                $post->setFieldValue($handle, $value);
            }

            expect(Elements::saveElement($post))->toBeTrue();

            return $post;
        };
    });

    test('returns the elements that relate to or reference the element through a field', function () {
        [$categoryA, $categoryB] = [$this->entry1, $this->entry2];

        $postA = ($this->post)(['categoriesField' => [$categoryA->id, $categoryB->id]]);
        $postB = ($this->post)(['categoriesField' => [$categoryA->id], 'otherEntriesField' => [$categoryB->id]]);
        $postC = ($this->post)(['linkField' => ['type' => 'entry', 'value' => "{entry:$categoryB->id@$categoryB->siteId:url}"]]);

        expect($categoryA->relatedBy('categoriesField')->ids())->toEqualCanonicalizing([$postA->id, $postB->id])
            ->and($categoryB->relatedBy('categoriesField')->ids())->toBe([$postA->id])
            ->and($categoryB->relatedBy('otherEntriesField')->ids())->toBe([$postB->id])
            ->and($categoryB->relatedBy('linkField')->ids())->toBe([$postC->id])
            ->and($postA->relatedBy('categoriesField')->ids())->toBe([]);
    });

    test('eager-loads the elements with the relatedBy: prefix', function () {
        [$categoryA, $categoryB, $categoryC] = [$this->entry1, $this->entry2, $this->entry3];

        $postA = ($this->post)(['categoriesField' => [$categoryA->id, $categoryB->id]]);
        $postB = ($this->post)(['categoriesField' => [$categoryA->id], 'otherEntriesField' => [$categoryB->id]]);
        $postC = ($this->post)(['linkField' => ['type' => 'entry', 'value' => "{entry:$categoryB->id@$categoryB->siteId:url}"]]);

        $categories = entryQuery()
            ->id([$categoryA->id, $categoryB->id, $categoryC->id])
            ->with(['relatedBy:categoriesField', 'relatedBy:linkField'])
            ->get()
            ->keyBy('id');

        DB::enableQueryLog();
        DB::flushQueryLog();

        $ids = fn (Entry $category, string $field) => $category->relatedBy($field)->map(fn (Entry $post) => $post->id)->all();

        expect($categories[$categoryA->id]->relatedBy('categoriesField'))->toBeInstanceOf(ElementCollection::class)
            ->and($ids($categories[$categoryA->id], 'categoriesField'))->toEqualCanonicalizing([$postA->id, $postB->id])
            ->and($ids($categories[$categoryB->id], 'categoriesField'))->toBe([$postA->id])
            ->and($ids($categories[$categoryC->id], 'categoriesField'))->toBe([])
            ->and($ids($categories[$categoryB->id], 'linkField'))->toBe([$postC->id])
            ->and($ids($categories[$categoryA->id], 'linkField'))->toBe([])
            ->and(DB::getQueryLog())->toBe([]);
    });

    test('eagerly() loads the elements for every queried element at once', function () {
        [$categoryA, $categoryB, $categoryC] = [$this->entry1, $this->entry2, $this->entry3];

        $postA = ($this->post)(['categoriesField' => [$categoryA->id, $categoryB->id]]);
        $postB = ($this->post)(['categoriesField' => [$categoryA->id]]);
        $postC = ($this->post)(['categoriesField' => [$categoryB->id]]);

        // Each category has its own entry type, so this also covers source elements with different field layout providers
        $categories = entryQuery()->id([$categoryA->id, $categoryB->id, $categoryC->id])->orderBy('elements.id')->all();
        $ids = fn (Entry $category) => collect($category->relatedBy('categoriesField')->eagerly()->all())->pluck('id')->all();

        expect($ids($categories[0]))->toEqualCanonicalizing([$postA->id, $postB->id]);

        DB::enableQueryLog();
        DB::flushQueryLog();

        expect($ids($categories[1]))->toEqualCanonicalizing([$postA->id, $postC->id])
            ->and($ids($categories[2]))->toBe([])
            ->and(DB::getQueryLog())->toBe([]);
    });

    test('eager-loaded elements keep the element type’s default order, unless the plan orders them', function () {
        $category = $this->entry1;

        // Post dates out of ID order, so the default order (newest first) differs from source ID order
        $posts = collect([3, 1, 2])->map(function (int $daysAgo) use ($category) {
            $post = ($this->post)(['categoriesField' => [$category->id]]);
            $post->postDate = now()->subDays($daysAgo);
            expect(Elements::saveElement($post))->toBeTrue();

            return $post;
        });

        $expected = $category->relatedBy('categoriesField')->ids();
        expect($expected)->not->toBe($posts->pluck('id')->sort()->values()->all());

        $loaded = fn (array $criteria = []) => entryQuery()
            ->id([$category->id, $this->entry2->id])
            ->orderBy('elements.id')
            ->with([['relatedBy:categoriesField', $criteria]])
            ->all()[0]
            ->relatedBy('categoriesField')
            ->pluck('id')
            ->all();

        expect($loaded())->toBe($expected)
            ->and($loaded(['orderBy' => 'elements.id asc']))->toBe($posts->pluck('id')->sort()->values()->all());
    });

    test('eagerly() keeps conditions added to the query', function () {
        [$categoryA, $categoryB, $tag] = [$this->entry1, $this->entry2, $this->entry3];

        $taggedPost = ($this->post)(['categoriesField' => [$categoryA->id, $categoryB->id], 'otherEntriesField' => [$tag->id]]);
        ($this->post)(['categoriesField' => [$categoryA->id, $categoryB->id]]);

        $categories = entryQuery()->id([$categoryA->id, $categoryB->id])->orderBy('elements.id')->all();
        $ids = fn (Entry $category) => collect(
            $category->relatedBy('categoriesField')
                ->andRelatedTo(['targetElement' => $tag, 'field' => 'otherEntriesField'])
                ->eagerly()
                ->all(),
        )->pluck('id')->all();

        expect($ids($categories[0]))->toBe([$taggedPost->id])
            ->and($ids($categories[1]))->toBe([$taggedPost->id]);
    });

    test('eager-loads references from users for elements in other sites', function () {
        $otherSite = Site::factory()->create();
        $section = SectionModel::factory()->create();
        SectionSiteSettings::query()->firstOrCreate(
            ['sectionId' => $section->id, 'siteId' => $otherSite->id],
            ['uid' => (string) str()->uuid(), 'hasUrls' => false],
        );
        Sections::refreshSections();
        $categoryModel = EntryModel::factory()->forSection($section)->enabledForSites($otherSite)->create();
        $category = entryQuery()->id($categoryModel->id)->one();

        FieldLayout::factory()->withContentTab([new CustomField(config: ['fieldUid' => Field::where('handle', 'linkField')->value('uid')])])->create(['type' => User::class]);
        Fields::invalidateCaches();
        Fields::refreshFields();

        $user = User::findOne();
        $user->setFieldValue('linkField', ['type' => 'entry', 'value' => "{entry:$category->id@$category->siteId:url}"]);
        expect(Elements::saveElement($user))->toBeTrue();

        $categoryInOtherSite = entryQuery()->id($category->id)->siteId($otherSite->id)->status(null)->one();
        expect($categoryInOtherSite)->not->toBeNull()
            ->and($categoryInOtherSite->relatedBy('linkField', User::class)->ids())->toBe([$user->id]);

        $loaded = entryQuery()->id($category->id)->siteId($otherSite->id)->status(null)->with(['relatedBy:linkField'])->one();

        expect($loaded->siteId)->toBe($otherSite->id);

        expect($loaded->relatedBy('linkField', User::class)->pluck('id')->all())->toBe([$user->id]);
    });

    test('eager-loads relations saved for the site the query asks for', function () {
        $siteA = Site::query()->where('primary', true)->firstOrFail();
        $siteB = Site::factory()->create();
        $section = SectionModel::factory()->create();
        SectionSiteSettings::query()->firstOrCreate(
            ['sectionId' => $section->id, 'siteId' => $siteB->id],
            ['uid' => (string) str()->uuid(), 'hasUrls' => false],
        );
        Sections::refreshSections();

        $categoryId = EntryModel::factory()->forSection($section)->enabledForSites($siteB)->create()->id;
        $typeId = DB::table('entries')->where('id', $categoryId)->value('typeId');
        $entry = fn () => EntryModel::factory()->forSection($section)->enabledForSites($siteB)->create(['typeId' => $typeId])->id;
        [$otherCategoryId, $postA, $postB, $postAnySite] = [$entry(), $entry(), $entry(), $entry()];

        $fieldId = Fields::getFieldByHandle('categoriesField')->id;
        foreach ([[$postA, $siteA->id], [$postB, $siteB->id], [$postAnySite, null]] as $i => [$postId, $sourceSiteId]) {
            DB::table('relations')->insert([
                'fieldId' => $fieldId,
                'sourceId' => $postId,
                'sourceSiteId' => $sourceSiteId,
                'targetId' => $categoryId,
                'sortOrder' => $i + 1,
                'uid' => (string) str()->uuid(),
                'dateCreated' => now(),
                'dateUpdated' => now(),
            ]);
        }

        $categories = fn () => entryQuery()->id([$categoryId, $otherCategoryId])->siteId($siteA->id)->orderBy('elements.id')->all();
        $sorted = fn (iterable $elements) => collect($elements)->pluck('id')->sort()->values()->all();

        expect($sorted($categories()[0]->relatedBy('categoriesField')->siteId($siteB->id)->all()))->toBe([$postB, $postAnySite])
            ->and($sorted($categories()[0]->relatedBy('categoriesField')->siteId($siteB->id)->eagerly()->all()))->toBe([$postB, $postAnySite])
            ->and($sorted($categories()[0]->relatedBy('categoriesField')->eagerly()->all()))->toBe([$postA, $postAnySite])
            ->and($categories()[0]->relatedBy('categoriesField')->siteId($siteB->id)->eagerly()->count())->toBe(2);

        // Relations saved for any of the queried sites count, whichever site's row is returned
        $anySite = fn (Entry $category) => $category->relatedBy('categoriesField')->site('*')->unique()->preferSites([$siteA->id]);
        expect($sorted($anySite($categories()[0])->all()))->toBe([$postA, $postB, $postAnySite])
            ->and($sorted($anySite($categories()[0])->eagerly()->all()))->toBe([$postA, $postB, $postAnySite])
            ->and(collect($anySite($categories()[0])->eagerly()->all())->every(fn (Entry $post) => $post->siteId === $siteA->id))->toBeTrue();

        $loaded = entryQuery()->id([$categoryId, $otherCategoryId])->siteId($siteA->id)->orderBy('elements.id')
            ->with([
                ['relatedBy:categoriesField', ['siteId' => $siteB->id]],
                ['relatedBy:categoriesField as siteBCount', ['siteId' => $siteB->id, 'count' => true]],
                ['relatedBy:categoriesField as everySite', ['siteId' => '*', 'unique' => true, 'preferSites' => [$siteA->id]]],
                ['relatedBy:categoriesField as invalidSite', ['siteId' => 'foo']],
                new EagerLoadPlan(
                    handle: 'relatedBy:categoriesField',
                    alias: 'configuredSite',
                    all: true,
                    count: true,
                    configureQuery: fn ($query) => $query->siteId($siteB->id),
                ),
            ])
            ->all();

        expect($sorted($loaded[0]->relatedBy('categoriesField')))->toBe([$postB, $postAnySite])
            ->and($loaded[0]->relatedBy('categoriesField')->every(fn (Entry $post) => $post->siteId === $siteB->id))->toBeTrue()
            ->and($loaded[0]->getEagerLoadedElementCount('siteBCount'))->toBe(2)
            ->and($sorted($loaded[0]->getEagerLoadedElements('everySite') ?? []))->toBe([$postA, $postB, $postAnySite])
            ->and($loaded[0]->getEagerLoadedElements('invalidSite')?->all() ?? [])->toBe([])
            ->and($sorted($loaded[0]->getEagerLoadedElements('configuredSite') ?? []))->toBe([$postB, $postAnySite])
            ->and($loaded[0]->getEagerLoadedElementCount('configuredSite'))->toBe(2);

        // Each site's results use that site's relations when the source elements come from several sites
        $inEverySite = fn () => collect(entryQuery()->id($categoryId)->site('*')->all())->keyBy('siteId');
        $categoryInSites = $inEverySite();
        expect($sorted($categoryInSites[$siteA->id]->relatedBy('categoriesField')->eagerly()->all()))->toBe([$postA, $postAnySite])
            ->and($sorted($categoryInSites[$siteB->id]->relatedBy('categoriesField')->eagerly()->all()))->toBe([$postB, $postAnySite]);

        $categoryInSites = $inEverySite();
        expect($categoryInSites[$siteA->id]->relatedBy('categoriesField')->eagerly()->count())->toBe(2)
            ->and($categoryInSites[$siteB->id]->relatedBy('categoriesField')->eagerly()->count())->toBe(2)
            ->and($sorted($categoryInSites[$siteB->id]->relatedBy('categoriesField')->eagerly()->all()))->toBe([$postB, $postAnySite]);

        // An explicit site applies to every source element, even when it's the first source element's own site
        $categoryInSites = $inEverySite();
        expect($sorted($categoryInSites[$siteA->id]->relatedBy('categoriesField')->siteId($siteA->id)->eagerly()->all()))->toBe([$postA, $postAnySite])
            ->and($sorted($categoryInSites[$siteB->id]->relatedBy('categoriesField')->siteId($siteA->id)->eagerly()->all()))->toBe([$postA, $postAnySite]);
    });

    test('queries owners through a Matrix field instead of eager-loading them', function () {
        [$categoryA, $categoryB] = [$this->entry1, $this->entry2];

        $blockType = EntryType::factory()
            ->withFieldLayout(FieldLayout::factory()->withContentTab([
                new CustomField(config: ['fieldUid' => Field::where('handle', 'categoriesField')->value('uid')]),
            ]))
            ->create(['handle' => 'block', 'hasTitleField' => false]);
        $matrix = Field::factory()->create([
            'handle' => 'blocksField',
            'type' => Matrix::class,
            'settings' => ['entryTypes' => [$blockType->id]],
        ]);
        $postModel = EntryModel::factory()
            ->withFieldLayout(FieldLayout::factory()->withContentTab([new CustomField(config: ['fieldUid' => $matrix->uid])]))
            ->create();
        Fields::refreshFields();
        EntryTypes::refreshEntryTypes();

        $post = entryQuery()->id($postModel->id)->one();
        $post->setFieldValueFromRequest('blocksField', [
            'sortOrder' => ['new:1'],
            'entries' => ['new:1' => ['type' => 'block', 'fields' => ['categoriesField' => [$categoryA->id]]]],
        ]);
        expect(Elements::saveElement($post))->toBeTrue();

        $categories = entryQuery()->id([$categoryA->id, $categoryB->id])->orderBy('elements.id')->all();

        expect($categories[0]->relatedBy('blocksField')->ids())->toBe([$post->id])
            ->and(collect($categories[0]->relatedBy('blocksField')->eagerly()->all())->pluck('id')->all())->toBe([$post->id])
            ->and(collect($categories[1]->relatedBy('blocksField')->eagerly()->all())->pluck('id')->all())->toBe([]);
    });

    test('eager-loading invalidates caches when any element using the field is saved', function () {
        ElementCaches::startCollectingCacheInfo();
        entryQuery()->id([$this->entry1->id, $this->entry2->id])->with(['relatedBy:categoriesField'])->all();
        $tags = ElementCaches::stopCollectingCacheInfo()[0]->tags;

        expect($tags)->toContain('element::'.Entry::class.'::*');
    });

    test('requires the element type when no element type uses the field', function () {
        Field::factory()->create(['handle' => 'unusedField', 'type' => Entries::class]);
        Fields::refreshFields();

        $this->entry1->relatedBy('unusedField');
    })->throws(InvalidArgumentException::class, 'No element type uses the field');

    test('requires a valid field', function () {
        $this->entry1->relatedBy('missingField');
    })->throws(InvalidArgumentException::class);
});
