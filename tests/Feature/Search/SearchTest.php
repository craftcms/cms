<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Search\Events\AfterSearch;
use CraftCms\Cms\Search\Events\BeforeIndexKeywords;
use CraftCms\Cms\Search\Events\BeforeScoreResults;
use CraftCms\Cms\Search\Events\BeforeSearch;
use CraftCms\Cms\Search\Jobs\UpdateSearchIndex;
use CraftCms\Cms\Search\SearchQuery;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

// MySQL InnoDB fulltext indexes are not transactional — data inserted within
// a transaction is invisible to MATCH...AGAINST queries. Since RefreshDatabase
// wraps each test in a transaction, we disable fulltext and fall back to LIKE.
beforeEach(function () {
    if (DB::isMysql()) {
        app(CraftCms\Cms\Search\Search::class)->useFullText = false;
    }
});

function createIndexedEntry(string $title, ?string $slug = null): EntryModel
{
    $factory = EntryModel::factory()
        ->indexed()
        ->title($title);

    if ($slug !== null) {
        $factory = $factory->slug($slug);
    }

    return $factory->create();
}

describe('indexElementAttributes', function () {
    test('indexes element title in the search index', function () {
        $entry = createIndexedEntry('Hello World');

        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->where('attribute', 'title')
            ->exists()
        )->toBeTrue();
    });

    test('indexes element slug in the search index', function () {
        $entry = createIndexedEntry('Hello World', 'hello-world');

        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->where('attribute', 'slug')
            ->exists()
        )->toBeTrue();
    });

    test('replaces existing index data on re-index', function () {
        $entry = createIndexedEntry('Original Title');

        $entry->element->siteSettings->first()->update(['title' => 'Updated Title']);
        $element = Elements::getElementById($entry->id);
        Search::indexElementAttributes($element);

        $keywords = DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->where('attribute', 'title')
            ->value('keywords');

        expect($keywords)->toContain('updated title');
        expect($keywords)->not->toContain('original title');
    });

    test('fires BeforeIndexKeywords event', function () {
        Event::fake([BeforeIndexKeywords::class]);

        createIndexedEntry('Test Entry');

        Event::assertDispatched(BeforeIndexKeywords::class);
    });

    test('BeforeIndexKeywords event can cancel indexing', function () {
        Event::listen(function (BeforeIndexKeywords $event) {
            if ($event->attribute === 'title') {
                $event->isValid = false;
            }
        });

        $entry = createIndexedEntry('Test Entry');

        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->where('attribute', 'title')
            ->exists()
        )->toBeFalse();
    });

    test('BeforeIndexKeywords event can modify keywords', function () {
        Event::listen(function (BeforeIndexKeywords $event) {
            if ($event->attribute === 'title') {
                $event->keywords = 'custom keywords';
            }
        });

        $entry = createIndexedEntry('Test Entry');

        $keywords = DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->where('attribute', 'title')
            ->value('keywords');

        expect($keywords)->toContain('custom keywords');
    });

    test('indexes with specific field handles', function () {
        $entry = createIndexedEntry('Test Entry');

        $element = Elements::getElementById($entry->id);
        $result = Search::indexElementAttributes($element, ['nonExistentField']);

        expect($result)->toBeTrue();
    });
});

describe('searchElements', function () {
    test('finds elements by title', function () {
        $entry1 = createIndexedEntry('Unique Searchable Title');
        $entry2 = createIndexedEntry('Something Else Entirely');

        $query = entryQuery()->search('Unique Searchable');

        expect($query->count())->toBe(1);
        expect($query->one()->id)->toBe($entry1->id);
    });

    test('returns empty results for non-matching query', function () {
        createIndexedEntry('Hello World');

        expect(entryQuery()->search('zzzznonexistent')->count())->toBe(0);
    });

    test('finds elements by slug', function () {
        $entry = createIndexedEntry('My Title', 'my-custom-slug');

        expect(entryQuery()->search('custom slug')->count())->toBe(1);
    });

    test('scores title matches higher than slug matches', function () {
        $entry1 = createIndexedEntry('Alpha', 'searchterm');
        $entry2 = createIndexedEntry('Searchterm', 'something-else');

        $results = entryQuery()->search('searchterm')->orderByDesc('score')->get();

        expect($results)->toHaveCount(2);
        expect($results[0]->id)->toBe($entry2->id);
    });

    test('fires BeforeSearch event', function () {
        createIndexedEntry('Test');

        Event::fake([BeforeSearch::class]);

        $elementQuery = entryQuery()->search('Test');
        Search::searchElements($elementQuery);

        Event::assertDispatched(BeforeSearch::class);
    });

    test('fires BeforeScoreResults event', function () {
        createIndexedEntry('Test');

        Event::fake([BeforeScoreResults::class]);

        $elementQuery = entryQuery()->search('Test');
        Search::searchElements($elementQuery);

        Event::assertDispatched(BeforeScoreResults::class);
    });

    test('fires AfterSearch event', function () {
        createIndexedEntry('Test');

        Event::fake([AfterSearch::class]);

        $elementQuery = entryQuery()->search('Test');
        Search::searchElements($elementQuery);

        Event::assertDispatched(AfterSearch::class);
    });

    test('AfterSearch event can override scores', function () {
        $entry1 = createIndexedEntry('Apple');
        $entry2 = createIndexedEntry('Banana');

        $siteId = Sites::getCurrentSite()->id;

        Event::listen(function (AfterSearch $event) use ($entry1, $entry2, $siteId) {
            $event->scores = [
                "{$entry2->id}-{$siteId}" => 100,
                "{$entry1->id}-{$siteId}" => 1,
            ];
        });

        $results = entryQuery()->search('Apple')->orderByDesc('score')->get();

        expect($results)->toHaveCount(2);
        expect($results[0]->id)->toBe($entry2->id);
    });

    test('BeforeScoreResults event can override scores', function () {
        $entry1 = createIndexedEntry('Cherry');
        $entry2 = createIndexedEntry('Date');

        $siteId = Sites::getCurrentSite()->id;

        Event::listen(function (BeforeScoreResults $event) use ($entry1, $entry2, $siteId) {
            $event->scores = [
                "{$entry2->id}-{$siteId}" => 100,
                "{$entry1->id}-{$siteId}" => 1,
            ];
        });

        $results = entryQuery()->search('Cherry')->orderByDesc('score')->get();

        expect($results)->toHaveCount(2);
        expect($results[0]->id)->toBe($entry2->id);
    });
});

describe('normalizeSearchQuery', function () {
    test('returns SearchQuery as-is', function () {
        $searchQuery = new SearchQuery('foo');
        $result = Search::normalizeSearchQuery($searchQuery);

        expect($result)->toBe($searchQuery);
    });

    test('converts string to SearchQuery', function () {
        $result = Search::normalizeSearchQuery('foo bar');

        expect($result)->toBeInstanceOf(SearchQuery::class);
        expect($result->getQuery())->toBe('foo bar');
    });

    test('converts array with query key to SearchQuery', function () {
        $result = Search::normalizeSearchQuery([
            'query' => 'foo bar',
            'subLeft' => true,
        ]);

        expect($result)->toBeInstanceOf(SearchQuery::class);
        expect($result->getQuery())->toBe('foo bar');

        $tokens = $result->getTokens();
        expect($tokens[0]->subLeft)->toBeTrue();
    });
});

describe('createDbQuery', function () {
    test('returns a query builder for valid search', function () {
        createIndexedEntry('Findable Entry');

        $query = Search::createDbQuery('Findable', entryQuery());

        expect($query)->not->toBeFalse();
    });

    test('returns false for empty search tokens', function () {
        $query = Search::createDbQuery('', entryQuery());

        expect($query)->toBeFalse();
    });

    test('supports terms with quotes via bound parameters', function () {
        createIndexedEntry("John's Favorite Entry");

        expect(entryQuery()->search("John's")->count())->toBe(1);
    });
});

describe('queueIndexElement', function () {
    test('dispatches UpdateSearchIndex job to queue', function () {
        Queue::fake();

        $entry = EntryModel::factory()->create();
        $element = Elements::getElementById($entry->id);

        Search::queueIndexElement($element, ['title']);

        Queue::assertPushed(UpdateSearchIndex::class);
    });

    test('creates a search index queue record', function () {
        Queue::fake();

        $entry = EntryModel::factory()->create();
        $element = Elements::getElementById($entry->id);

        Search::queueIndexElement($element, ['title', 'slug']);

        expect(DB::table(Table::SEARCHINDEXQUEUE)
            ->where('elementId', $entry->id)
            ->exists()
        )->toBeTrue();

        expect(DB::table(Table::SEARCHINDEXQUEUE_FIELDS)
            ->whereIn('fieldHandle', ['title', 'slug'])
            ->count()
        )->toBe(2);
    });
});

describe('indexElementIfQueued', function () {
    test('indexes element when queued', function () {
        $entry = EntryModel::factory()->create();
        $entry->element->siteSettings->first()->update(['title' => 'Queued Entry']);

        $siteId = Sites::getCurrentSite()->id;

        $jobId = DB::table(Table::SEARCHINDEXQUEUE)->insertGetId([
            'elementId' => $entry->id,
            'siteId' => $siteId,
            'reserved' => false,
        ]);

        DB::table(Table::SEARCHINDEXQUEUE_FIELDS)->insert([
            'jobId' => $jobId,
            'fieldHandle' => 'title',
        ]);

        Search::indexElementIfQueued($entry->id, $siteId, Entry::class);

        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->exists()
        )->toBeTrue();

        expect(DB::table(Table::SEARCHINDEXQUEUE)
            ->where('id', $jobId)
            ->exists()
        )->toBeFalse();
    });

    test('does nothing when element is not queued', function () {
        Search::indexElementIfQueued(99999, 1);

        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', 99999)
            ->exists()
        )->toBeFalse();
    });

    test('does not process already reserved jobs', function () {
        $entry = EntryModel::factory()->create();
        $siteId = Sites::getCurrentSite()->id;

        DB::table(Table::SEARCHINDEXQUEUE)->insert([
            'elementId' => $entry->id,
            'siteId' => $siteId,
            'reserved' => true,
        ]);

        Search::indexElementIfQueued($entry->id, $siteId, Entry::class);

        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->exists()
        )->toBeFalse();
    });
});

describe('deleteOrphanedIndexes', function () {
    test('removes search index rows for non-existent elements', function () {
        $entry = createIndexedEntry('Valid Entry');

        $orphanedRow = [
            'elementId' => 999999,
            'attribute' => 'title',
            'fieldId' => '0',
            'siteId' => Sites::getCurrentSite()->id,
            'keywords' => ' orphaned ',
        ];

        if (DB::connection()->isPgsql()) {
            $orphanedRow['keywords_vector'] = 'orphaned';
        }

        DB::table(Table::SEARCHINDEX)->insert($orphanedRow);

        $countBefore = DB::table(Table::SEARCHINDEX)->count();

        Search::deleteOrphanedIndexes();

        expect(DB::table(Table::SEARCHINDEX)->count())->toBe($countBefore - 1);
        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', 999999)
            ->exists()
        )->toBeFalse();
        expect(DB::table(Table::SEARCHINDEX)
            ->where('elementId', $entry->id)
            ->exists()
        )->toBeTrue();
    });
});

describe('deleteOrphanedIndexJobs', function () {
    test('removes search index queue rows for non-existent elements', function () {
        $entry = EntryModel::factory()->create();

        DB::table(Table::SEARCHINDEXQUEUE)->insert([
            'elementId' => $entry->id,
            'siteId' => 1,
            'reserved' => false,
        ]);

        DB::table(Table::SEARCHINDEXQUEUE)->insert([
            'elementId' => 999999,
            'siteId' => 1,
            'reserved' => false,
        ]);

        Search::deleteOrphanedIndexJobs();

        expect(DB::table(Table::SEARCHINDEXQUEUE)
            ->where('elementId', $entry->id)
            ->exists()
        )->toBeTrue();
        expect(DB::table(Table::SEARCHINDEXQUEUE)
            ->where('elementId', 999999)
            ->exists()
        )->toBeFalse();
    });
});

describe('shouldCallSearchElements', function () {
    test('returns false by default', function () {
        expect(Search::shouldCallSearchElements(entryQuery()))->toBeFalse();
    });
});
