<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\gql\mutations;

use Codeception\Stub\Expected;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\test\TestCase;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Queries\EntryQuery;
use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use GraphQL\Type\Definition\ResolveInfo;
use Throwable;

class EntryMutationResolverTest extends TestCase
{
    /**
     * Test whether various argument combos set the correct scenario on the element.
     *
     * @param array $arguments
     * @param string $scenario
     * @throws Throwable
     * @dataProvider saveEntryDataProvider
     */
    public function testSavingDraftOrEntrySetsRelevantScenario(array $arguments, string $scenario): void
    {
        $entry = $this->make(Entry::class, [
            'title' => 'Test title',
            'getType' => new EntryType(),
        ]);

        $identifyQuery = $this->make(EntryQuery::class, [
            'first' => $entry,
            'siteId' => function() use (&$identifyQuery) {
                return $identifyQuery;
            },
            'status' => function() use (&$identifyQuery) {
                return $identifyQuery;
            },
            'id' => function() use (&$identifyQuery) {
                return $identifyQuery;
            },
            'drafts' => function() use (&$identifyQuery) {
                return $identifyQuery;
            },
            'draftId' => function() use (&$identifyQuery) {
                return $identifyQuery;
            },
        ]);

        $createQuery = $this->make(EntryQuery::class, [
            'siteId' => function() use (&$createQuery) {
                return $createQuery;
            },
            'status' => function() use (&$createQuery) {
                return $createQuery;
            },
        ]);

        $resolver = $this->make(EntryMutationResolver::class, [
            'getEntryElement' => $entry,
            'identifyEntry' => $identifyQuery,
            'recursivelyNormalizeArgumentValues' => $arguments,
        ]);

        \CraftCms\Cms\Support\Facades\Elements::partialMock()
            ->shouldReceive('saveElement')->andReturn(true)
            ->shouldReceive('createElementQuery')->andReturn($createQuery);

        $resolver->saveEntry(null, $arguments, null, $this->make(ResolveInfo::class));
        self::assertSame($scenario, $entry->scenario);
    }

    /**
     * Test that saving new entries does not attempt to identify them in the database.
     *
     * @param array $arguments
     * @param bool $identifyCalled
     * @throws Throwable
     * @dataProvider saveNewEntryDataProvider
     */
    public function testSavingNewEntryDoesNotSearchForIt(array $arguments, bool $identifyCalled): void
    {
        $entry = $this->make(Entry::class, [
            'title' => 'Test title',
            'getType' => new EntryType(),
        ]);

        $query = $this->make(EntryQuery::class, [
            'first' => $entry,
            'siteId' => function() use (&$query) {
                return $query;
            },
            'status' => function() use (&$query) {
                return $query;
            },
            'id' => function() use (&$query) {
                return $query;
            },
            'drafts' => function() use (&$query) {
                return $query;
            },
            'draftId' => function() use (&$query) {
                return $query;
            },
        ]);

        $resolver = $this->make(EntryMutationResolver::class, [
            'getEntryElement' => $entry,
            'recursivelyNormalizeArgumentValues' => $arguments,
            'identifyEntry' => $identifyCalled ? Expected::atLeastOnce($query) : Expected::never($query),
        ]);

        \CraftCms\Cms\Support\Facades\Elements::partialMock()
            ->shouldReceive('saveElement')->andReturn(true)
            ->shouldReceive('createElementQuery')->andReturn($query);

        $resolver->saveEntry(null, $arguments, null, $this->make(ResolveInfo::class));
    }

    public static function saveEntryDataProvider(): array
    {
        return [
            [['draftId' => 5], Element::SCENARIO_ESSENTIALS],
            [['id' => 5, 'enabled' => true], Element::SCENARIO_LIVE],
            [['id' => 5, 'enabled' => false], Element::SCENARIO_DEFAULT],
        ];
    }

    public static function saveNewEntryDataProvider(): array
    {
        return [
            [['draftId' => 5], true],
            [['id' => 5, 'enabled' => true], true],
            [['id' => 5, 'enabled' => false], true],
            //[['title' => 'Chet Faker', 'enabled' => false], false],
        ];
    }
}
