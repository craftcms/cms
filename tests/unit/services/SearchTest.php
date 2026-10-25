<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Codeception\Test\Unit;
use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\User;
use craft\events\IndexKeywordsEvent;
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\services\Search;
use craft\test\TestCase;
use crafttests\fixtures\UserFixture;

/**
 * Unit tests for the search service
 *
 * @todo There are MySQL and PostgreSQL specific search tests that need to be performed.
 *
 * Searching and some of the commands run in this test are documented here:
 * https://craftcms.com/docs/5.x/system/searching.html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SearchTest extends TestCase
{
    /**
     * @var Search
     */
    protected Search $search;

    public function _fixtures(): array
    {
        return [
            'users' => [
                'class' => UserFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider searchElementsDataProvider
     * @param array $usernameOrEmailsForResult
     * @param array $usernameOrEmailsForQuery
     * @param string $searchQuery
     */
    public function testSearchElements(array $usernameOrEmailsForResult, array $usernameOrEmailsForQuery, string $searchQuery): void
    {
        // Repackage the dataProvider data into something that can be used by the filter function
        $result = $this->_usernameEmailArrayToScoreKeyList($usernameOrEmailsForResult);
        $elementIds = array_map(fn(string $key) => explode('-', $key)[0], $this->_usernameEmailArrayToScoreKeyList($usernameOrEmailsForQuery));
        $elementQuery = User::find()
            ->id($elementIds ?: null)
            ->search($searchQuery);

        // Filter them
        $filtered = array_keys($this->search->searchElements($elementQuery));

        sort($result, SORT_NATURAL);
        sort($filtered, SORT_NATURAL);

        self::assertSame($result, $filtered);
    }

    /*
     * Creates a new User(); and runs indexElementAttributes on it to see how its property values are stored in the database.
     *
     * @todo test with fields and multisite using entries
     */
    public function testIndexElementAttributes(): void
    {
        // Create a user
        $user = new User();
        $user->active = true;
        $user->username = 'testIndexElementAttributes1';
        $user->email = 'testIndexElementAttributes1@test.com';
        $user->firstName = 'john';
        $user->lastName = 'wilkerson';
        $user->id = 1;

        // Index them.
        $this->search->indexElementAttributes($user);

        // Get the data from the DB
        $searchIndex = (new Query())->from([Table::SEARCHINDEX])->where(['elementId' => $user->id])->all();

        self::assertSame(' testindexelementattributes1 test com ', $this->_getSearchIndexValueByAttribute('email', $searchIndex));
        self::assertSame(' john ', $this->_getSearchIndexValueByAttribute('firstname', $searchIndex));
        self::assertSame(' wilkerson ', $this->_getSearchIndexValueByAttribute('lastname', $searchIndex));
    }

    public function testIndexElementAttributesInsertsKeywordsInOneStatement(): void
    {
        $user = $this->_makeUser(2);

        $inserts = array_filter(
            $this->_recordStatements(fn() => $this->search->indexElementAttributes($user)),
            fn(string $sql) => (bool)preg_match('/^\s*INSERT\s+INTO\s+\S*searchindex\S*\s/i', $sql),
        );
        self::assertCount(1, $inserts);

        // One row for each searchable attribute
        $searchIndex = $this->_searchIndexRows($user->id);
        $attributes = array_map('strtolower', ElementHelper::searchableAttributes($user));
        sort($attributes);
        $indexedAttributes = array_column($searchIndex, 'attribute');
        sort($indexedAttributes);
        self::assertSame($attributes, $indexedAttributes);

        self::assertSame(' testindexelementattributes2 test com ', $this->_getSearchIndexValueByAttribute('email', $searchIndex));
        self::assertSame(' john ', $this->_getSearchIndexValueByAttribute('firstname', $searchIndex));
        self::assertSame(' wilkerson ', $this->_getSearchIndexValueByAttribute('lastname', $searchIndex));
    }

    public function testBeforeIndexKeywordsEventCanSkipOrModifyKeywords(): void
    {
        $user = $this->_makeUser(3);
        $firedFor = [];

        $handler = function(IndexKeywordsEvent $event) use (&$firedFor) {
            $firedFor[] = $event->attribute;
            if ($event->attribute === 'lastname') {
                $event->isValid = false;
            } elseif ($event->attribute === 'firstname') {
                $event->keywords = 'modified keywords';
            }
        };

        $this->search->on(Search::EVENT_BEFORE_INDEX_KEYWORDS, $handler);
        try {
            $this->search->indexElementAttributes($user);
        } finally {
            $this->search->off(Search::EVENT_BEFORE_INDEX_KEYWORDS, $handler);
        }

        // The event fired once per searchable attribute
        self::assertSame(array_map('strtolower', ElementHelper::searchableAttributes($user)), $firedFor);

        $searchIndex = $this->_searchIndexRows($user->id);
        self::assertNotContains('lastname', array_column($searchIndex, 'attribute'));
        self::assertSame(' modified keywords ', $this->_getSearchIndexValueByAttribute('firstname', $searchIndex));
        self::assertSame(' testindexelementattributes3 test com ', $this->_getSearchIndexValueByAttribute('email', $searchIndex));
    }

    public function testIndexElementAttributesWithAllKeywordsSkipped(): void
    {
        $user = $this->_makeUser(4);
        $this->search->indexElementAttributes($user);
        self::assertNotEmpty($this->_searchIndexRows($user->id));

        $handler = fn(IndexKeywordsEvent $event) => $event->isValid = false;
        $this->search->on(Search::EVENT_BEFORE_INDEX_KEYWORDS, $handler);
        try {
            $this->search->indexElementAttributes($user);
        } finally {
            $this->search->off(Search::EVENT_BEFORE_INDEX_KEYWORDS, $handler);
        }

        // The old keywords were still cleared out
        self::assertEmpty($this->_searchIndexRows($user->id));
    }

    public function testLargeKeywordBatchesAreSplitAcrossStatements(): void
    {
        $user = $this->_makeUser(5);
        // ~60KB of short words (Postgres doesn't allow tsvector words over 2046 bytes)
        $keywords = ' ' . str_repeat('lorem ', 10000);
        $rows = [];
        for ($i = 0; $i < 20; $i++) {
            $rows[] = [
                'elementId' => $user->id,
                'attribute' => "attr$i",
                'fieldId' => '0',
                'siteId' => Craft::$app->getSites()->getPrimarySite()->id,
                'keywords' => $keywords,
            ];
        }
        if (Craft::$app->getDb()->getIsPgsql()) {
            $rows = array_map(fn(array $row) => $row + ['keywords_vector' => $keywords], $rows);
        }

        $inserts = array_filter(
            $this->_recordStatements(fn() => $this->invokeMethod($this->search, '_insertKeywordsRows', [$rows])),
            fn(string $sql) => (bool)preg_match('/^\s*INSERT\s+INTO\s+\S*searchindex\S*\s/i', $sql),
        );

        // 20 rows × ~60KB should be split into two statements, to stay under ~1MB each
        self::assertCount(2, $inserts);
        $searchIndex = $this->_searchIndexRows($user->id);
        self::assertCount(20, $searchIndex);
        foreach ($searchIndex as $row) {
            self::assertSame($keywords, $row['keywords']);
        }
    }

    /**
     * Provide an array with input user names
     *
     * @return array
     */
    public static function searchElementsDataProvider(): array
    {
        return [
            [['user1'], ['user1', 'user2', 'user3', 'user4'], 'user1@crafttest.com'],

            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user'],
            [['user4', 'user1', 'user2', 'user3'], [], 'user'],
            [['user1', 'user2', 'user3'], ['user1', 'user2', 'user3'], 'user'],
            [['user4'], ['user1', 'user2', 'user3', 'user4'], 'user someemail'],
            [[], ['user1', 'user2', 'user3'], 'user someemail'],

            // This should work. If you want an empty slug you should try: -slug:*
            [[], ['user1', 'user2', 'user3', 'user4'], 'slug:'],
            [[], ['user1', 'user2', 'user3', 'user4'], 'slug:""'],

            // User4 goes first as it has both user and someemail keywords
            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user OR someemail'],
            [['user4', 'user1'], ['user1', 'user2', 'user3', 'user4'], 'someemail OR -firstname:*'],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function _before(): void
    {
        parent::_before();

        $this->search = Craft::$app->getSearch();
    }

    /**
     * @param mixed $attributeName
     * @param iterable $searchIndex
     * @return string
     */
    private function _getSearchIndexValueByAttribute(mixed $attributeName, iterable $searchIndex): string
    {
        foreach (ArrayHelper::where($searchIndex, 'attribute', $attributeName) as $array) {
            if (isset($array['keywords'])) {
                return $array['keywords'];
            }
        }

        return '';
    }

    /**
     * @param array $usernameOrEmails
     * @return string[]
     */
    private function _usernameEmailArrayToScoreKeyList(array $usernameOrEmails): array
    {
        $scoreKeys = [];
        $usersService = Craft::$app->getUsers();

        foreach ($usernameOrEmails as $usernameOrEmail) {
            $user = $usersService->getUserByUsernameOrEmail($usernameOrEmail);
            $scoreKeys[] = sprintf('%s-%s', $user->id, $user->siteId);
        }

        return $scoreKeys;
    }

    private function _makeUser(int $num): User
    {
        $user = new User();
        $user->active = true;
        $user->username = "testIndexElementAttributes$num";
        $user->email = "testIndexElementAttributes$num@test.com";
        $user->firstName = 'john';
        $user->lastName = 'wilkerson';
        $user->id = 1000 + $num;
        Craft::$app->getDb()->createCommand()->delete(Table::SEARCHINDEX, ['elementId' => $user->id])->execute();
        return $user;
    }

    private function _searchIndexRows(int $elementId): array
    {
        return (new Query())->from([Table::SEARCHINDEX])->where(['elementId' => $elementId])->all();
    }

    /**
     * Records the SQL of every statement executed or queried within the callback.
     *
     * @return string[]
     */
    private function _recordStatements(callable $callback): array
    {
        $db = Craft::$app->getDb();
        $oldCommandClass = $db->commandClass;
        $db->commandClass = RecordingCommand::class;
        RecordingCommand::startRecording();

        try {
            $callback();
        } finally {
            $db->commandClass = $oldCommandClass;
        }

        return RecordingCommand::stopRecordingStatements();
    }
}
