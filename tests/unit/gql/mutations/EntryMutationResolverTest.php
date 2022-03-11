<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craftunit\gql\mutations;

use Codeception\Stub\Expected;
use craft\base\Element;
use craft\elements\db\EntryQuery;
use craft\elements\Entry;
use craft\gql\resolvers\mutations\Entry as EntryMutationResolver;
use craft\services\Elements;
use craft\test\TestCase;
use GraphQL\Type\Definition\ResolveInfo;

class EntryMutationResolverTest extends TestCase
{
    /**
     * Test whether various argument combos set the correct scenario on the element.
     *
     * @param $arguments
     * @param $scenario
     * @throws \Throwable
     * @dataProvider saveEntryDataProvider
     */
    public function testSavingDraftOrEntrySetsRelevantScenario($arguments, $scenario)
    {
        $entry = new Entry();

        $resolver = $this->make(EntryMutationResolver::class, [
            'getEntryElement' => $entry,
            'identifyEntry' => $this->make(EntryQuery::class, [
                'one' => $entry,
            ]),
            'recursivelyNormalizeArgumentValues' => $arguments,
        ]);

        \Craft::$app->set('elements', $this->make(Elements::class, [
            'saveElement' => true,
        ]));

        $resolver->saveEntry(null, $arguments, null, $this->make(ResolveInfo::class));
        $this->assertSame($scenario, $entry->scenario);
    }

    /**
     * Test that saving new entries does not attempt to identify them in the database.
     *
     * @param $arguments
     * @param $identifyCalled
     * @throws \Throwable
     * @dataProvider saveNewEntryDataProvider
     */
    public function testSavingNewEntryDoesNotSearchForIt($arguments, $identifyCalled)
    {
        $entry = new Entry();
        $query = $this->make(EntryQuery::class, [
            'one' => $entry,
        ]);

        $resolver = $this->make(EntryMutationResolver::class, [
            'getEntryElement' => $entry,
            'recursivelyNormalizeArgumentValues' => $arguments,
            'identifyEntry' => $identifyCalled ? Expected::atLeastOnce($query) : Expected::never($query),
        ]);

        \Craft::$app->set('elements', $this->make(Elements::class, [
            'saveElement' => true,
            'createElementQuery' => $query,
        ]));

        $entry = $resolver->saveEntry(null, $arguments, null, $this->make(ResolveInfo::class));
        $this->assertIsObject($entry);
    }

    public function saveEntryDataProvider()
    {
        return [
            [['draftId' => 5], Element::SCENARIO_ESSENTIALS],
            [['id' => 5, 'enabled' => true], Element::SCENARIO_LIVE],
            [['id' => 5, 'enabled' => false], Element::SCENARIO_DEFAULT],
        ];
    }
    public function saveNewEntryDataProvider()
    {
        return [
            [['draftId' => 5], true],
            [['id' => 5, 'enabled' => true], true],
            [['id' => 5, 'enabled' => false], true],
            [['title' => 'Chet Faker', 'enabled' => false], false],
        ];
    }
}
