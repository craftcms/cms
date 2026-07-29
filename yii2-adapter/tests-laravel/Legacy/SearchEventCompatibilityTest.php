<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\events\SearchEvent;
use craft\services\Search as LegacySearch;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Search\Events\SearchResultsResolving;
use CraftCms\Cms\Search\Events\SearchScoresResolving;
use CraftCms\Cms\Search\SearchQuery;

afterEach(function() {
    YiiEvent::off(LegacySearch::class, LegacySearch::EVENT_BEFORE_SCORE_RESULTS);
    YiiEvent::off(LegacySearch::class, LegacySearch::EVENT_AFTER_SEARCH);
});

test('legacy search events bridge result and score changes across the search lifecycle', function() {
    $results = [
        ['elementId' => 1, 'siteId' => 1],
    ];
    $resolvedResults = [
        ['elementId' => 2, 'siteId' => 1],
    ];

    YiiEvent::on(LegacySearch::class, LegacySearch::EVENT_BEFORE_SCORE_RESULTS, function(SearchEvent $event) use ($results, $resolvedResults) {
        expect($event->results)->toBe($results);

        $event->results = $resolvedResults;
        $event->scores = ['2-1' => 10];
    });
    YiiEvent::on(LegacySearch::class, LegacySearch::EVENT_AFTER_SEARCH, function(SearchEvent $event) use ($resolvedResults) {
        expect($event->results)->toBe($resolvedResults)
            ->and($event->scores)->toBe(['2-1' => 10]);

        $event->scores = ['2-1' => 20];
    });

    $elementQuery = Entry::find();
    $searchQuery = new SearchQuery('test');
    $resultsEvent = new SearchResultsResolving($elementQuery, $searchQuery, $results);

    event($resultsEvent);

    expect($resultsEvent->results)->toBe($resolvedResults);

    $scoresEvent = new SearchScoresResolving($elementQuery, $searchQuery, $resultsEvent->results, ['1-1' => 1]);

    event($scoresEvent);

    expect($scoresEvent->scores)->toBe(['2-1' => 20]);
});
