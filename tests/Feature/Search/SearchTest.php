<?php

declare(strict_types=1);

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Search\Events\KeywordsIndexing;
use CraftCms\Cms\Search\Events\SearchPerformed;
use CraftCms\Cms\Search\Events\SearchResultsResolving;
use CraftCms\Cms\Search\Events\SearchScoresResolving;
use CraftCms\Cms\Search\Events\SearchStarting;
use CraftCms\Cms\Search\Jobs\UpdateSearchIndex;
use CraftCms\Cms\Search\SearchQuery;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\Support\Facades\Sites;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

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

    test('fires KeywordsIndexing event', function () {
        Event::fake([KeywordsIndexing::class]);

        createIndexedEntry('Test Entry');

        Event::assertDispatched(KeywordsIndexing::class);
    });

    test('KeywordsIndexing event can cancel indexing', function () {
        Event::listen(function (KeywordsIndexing $event) {
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

    test('KeywordsIndexing event can modify keywords', function () {
        Event::listen(function (KeywordsIndexing $event) {
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

    test('fires SearchStarting event', function () {
        createIndexedEntry('Test');

        Event::fake([SearchStarting::class]);

        $elementQuery = entryQuery()->search('Test');
        Search::searchElements($elementQuery);

        Event::assertDispatched(SearchStarting::class);
    });

    test('fires SearchResultsResolving event', function () {
        createIndexedEntry('Test');

        Event::fake([SearchResultsResolving::class]);

        $elementQuery = entryQuery()->search('Test');
        Search::searchElements($elementQuery);

        Event::assertDispatched(SearchResultsResolving::class);
    });

    test('fires SearchScoresResolving event', function () {
        createIndexedEntry('Test');

        Event::fake([SearchScoresResolving::class]);

        $elementQuery = entryQuery()->search('Test');
        Search::searchElements($elementQuery);

        Event::assertDispatched(SearchScoresResolving::class);
    });

    test('fires SearchPerformed event', function () {
        createIndexedEntry('Test');

        Event::fake([SearchPerformed::class]);

        $elementQuery = entryQuery()->search('Test');
        Search::searchElements($elementQuery);

        Event::assertDispatched(SearchPerformed::class);
    });

    test('SearchScoresResolving event can override scores', function () {
        $entry1 = createIndexedEntry('Apple');
        $entry2 = createIndexedEntry('Banana');

        $siteId = Sites::getCurrentSite()->id;
        $performedScores = null;

        Event::listen(function (SearchScoresResolving $event) use ($entry1, $entry2, $siteId) {
            $event->scores = [
                "{$entry1->id}-{$siteId}" => 1,
                "{$entry2->id}-{$siteId}" => 100,
            ];
        });
        Event::listen(function (SearchPerformed $event) use (&$performedScores) {
            $performedScores = $event->scores;
        });

        $results = entryQuery()->search('Apple')->orderByDesc('score')->get();

        expect($results)->toHaveCount(2)
            ->and($results[0]->id)->toBe($entry2->id)
            ->and(array_keys($performedScores))->toBe([
                "{$entry2->id}-{$siteId}",
                "{$entry1->id}-{$siteId}",
            ]);
    });

    test('SearchResultsResolving changes are scored and observed', function () {
        $entry1 = createIndexedEntry('Cherry');
        $entry2 = createIndexedEntry('Cherry Date');

        $siteId = Sites::getCurrentSite()->id;
        $performed = null;

        Event::listen(function (SearchResultsResolving $event) use ($entry2) {
            $event->results = array_values(array_filter(
                $event->results,
                fn (array $result) => (int) $result['elementId'] === $entry2->id,
            ));
        });
        Event::listen(function (SearchPerformed $event) use (&$performed) {
            $performed = $event;
        });

        $scores = Search::searchElements(entryQuery()->search('Cherry'));

        expect($scores)
            ->toHaveKey("{$entry2->id}-{$siteId}")
            ->not->toHaveKey("{$entry1->id}-{$siteId}")
            ->and(array_unique(array_column($performed->results, 'elementId')))->toBe([$entry2->id])
            ->and($performed->scores)->toBe($scores);
    });

    test('nested searches retain their own scoring terms', function () {
        createIndexedEntry('Alpha');
        createIndexedEntry('Alpha Beta');

        $expectedOuterScores = Search::searchElements(entryQuery()->search('Alpha'));
        $expectedNestedScores = Search::searchElements(entryQuery()->search('Beta'));
        $nestedScores = null;

        Event::listen(function (SearchResultsResolving $event) use (&$nestedScores) {
            if ($event->query->getQuery() === 'Alpha') {
                $nestedScores = Search::searchElements(entryQuery()->search('Beta'));
            }
        });

        $outerScores = Search::searchElements(entryQuery()->search('Alpha'));

        expect($outerScores)->toBe($expectedOuterScores)
            ->and($nestedScores)->toBe($expectedNestedScores);
    });

    test('sequential searches retain their own scoring terms', function () {
        createIndexedEntry('Alpha');
        createIndexedEntry('Alpha Beta');

        $alphaScores = Search::searchElements(entryQuery()->search('Alpha'));
        $betaScores = Search::searchElements(entryQuery()->search('Beta'));

        expect(Search::searchElements(entryQuery()->search('Alpha')))->toBe($alphaScores)
            ->and(Search::searchElements(entryQuery()->search('Beta')))->toBe($betaScores);
    });

    test('failed nested searches do not clear outer scoring terms', function () {
        createIndexedEntry('Alpha');
        createIndexedEntry('Alpha Beta');

        $expectedScores = Search::searchElements(entryQuery()->search('Alpha'));
        $failedQuery = null;

        Event::listen(function (SearchResultsResolving $event) use (&$failedQuery) {
            if ($event->query->getQuery() === 'Alpha') {
                $failedQuery = Search::createDbQuery('', entryQuery());
            }
        });

        $scores = Search::searchElements(entryQuery()->search('Alpha'));

        expect($failedQuery)->toBeFalse()
            ->and($scores)->toBe($expectedScores);
    });

    test('concurrent searches retain their own scoring terms', function () {
        createIndexedEntry('Alpha');
        createIndexedEntry('Alpha Beta');

        $expectedScores = Search::searchElements(entryQuery()->search('Alpha'));
        $searchFiber = null;

        Event::listen(function (SearchResultsResolving $event) use (&$searchFiber) {
            if ($event->query->getQuery() === 'Alpha' && Fiber::getCurrent() === $searchFiber) {
                Fiber::suspend();
            }
        });

        $searchFiber = new Fiber(fn () => Search::searchElements(entryQuery()->search('Alpha')));
        $searchFiber->start();

        Search::searchElements(entryQuery()->search('Beta'));
        $searchFiber->resume();

        expect($searchFiber->getReturn())->toBe($expectedScores);
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
