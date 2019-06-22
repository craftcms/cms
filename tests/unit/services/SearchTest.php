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
use craft\helpers\ArrayHelper;
use craft\services\Search;
use crafttests\fixtures\UsersFixture;
use UnitTester;

/**
 * Unit tests for the search service
 *
 * @todo There are MySQL and PostgreSQL specific search tests that need to be performed.
 *
 * Searching and some of the commands run in this test are documented here:
 * https://docs.craftcms.com/v3/searching.html#supported-syntaxes
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SearchTest extends Unit
{
    // Protected Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Search
     */
    protected $search;

    // Public Methods
    // =========================================================================

    public function _fixtures(): array
    {
        return [
            'users' => [
                'class' => UsersFixture::class,
            ]
        ];
    }

    // Tests
    // =========================================================================

    /**
     * @dataProvider filterElementIdByQueryDataProvider
     *
     * @param $usernameOrEmailsForResult
     * @param $usernameOrEmailsForQuery
     * @param $query
     * @param bool $scoreResults
     * @param null $siteId
     * @param bool $returnScores
     */
    public function testFilterElementIdsByQuery($usernameOrEmailsForResult, $usernameOrEmailsForQuery, $query, $scoreResults = true, $siteId = null, $returnScores = false)
    {
        // Repackage the dataProvider data into something that can be used by the filter function
        $result = $this->_usernameEmailArrayToIdList($usernameOrEmailsForResult);
        $forQuery = $this->_usernameEmailArrayToIdList($usernameOrEmailsForQuery);

        // Filter them
        $filtered = $this->search->filterElementIdsByQuery($forQuery, $query, $scoreResults, $siteId, $returnScores);

        sort($result, SORT_NUMERIC);
        sort($filtered, SORT_NUMERIC);

        $this->assertSame($result, $filtered);
    }

    /**
     * @dataProvider filterScoresDataProvider
     *
     * @param $scoresAndNames
     * @param $usernameOrEmailsForQuery
     * @param $query
     * @param bool $scoreResults
     * @param null $siteId
     */
    public function testFilterScores($scoresAndNames, $usernameOrEmailsForQuery, $query, $scoreResults = true, $siteId = null)
    {
        // Repackage the dataProvider input into what the filter function will return.
        $result = $this->_scoreList($scoresAndNames);

        // Get the user ids to send into the filter function
        $forQuery = $this->_usernameEmailArrayToIdList($usernameOrEmailsForQuery);

        // Filter them
        $filtered = $this->search->filterElementIdsByQuery($forQuery, $query, $scoreResults, $siteId, true);

        $this->assertSame($result, $filtered);
    }

    /**
     *
     */
    public function testElementQueryReturnsInt()
    {
        $result = $this->_usernameEmailArrayToIdList(['user1', 'user2', 'user3'], true);
        $forQuery = $this->_usernameEmailArrayToIdList(['user1', 'user2', 'user3'], false);

        $filtered = $this->search->filterElementIdsByQuery($forQuery, 'user');

        $this->assertSame($result, $filtered);
    }

    /*
     * Creates a new User(); and runs indexElementAttributes on it to see how its property values are stored in the database.
     *
     * @todo test with fields and multisite using entries
     */
    public function testIndexElementAttributes()
    {
        // Create a user
        $user = new User();
        $user->username = 'testIndexElementAttributes1';
        $user->email = 'testIndexElementAttributes1@test.com';
        $user->firstName = 'john smith';
        $user->lastName = 'WIL K ER SON!';
        $user->id = '666';

        // Index them.
        $this->search->indexElementAttributes($user);

        // Get the data from the DB
        $searchIndex = (new Query())->from([Table::SEARCHINDEX])->where(['elementId' => $user->id])->all();

        $this->assertSame(' testindexelementattributes1 test com ', $this->_getSearchIndexValueByAttribute('email', $searchIndex));
        $this->assertSame(' john smith ', $this->_getSearchIndexValueByAttribute('firstname', $searchIndex));
        $this->assertSame(' wil k er son ', $this->_getSearchIndexValueByAttribute('lastname', $searchIndex));
        $this->assertSame(' john smith wil k er son ', $this->_getSearchIndexValueByAttribute('fullname', $searchIndex));
    }

    // Data Providers
    // =========================================================================

    /**
     * Provide an array with input user names
     *
     * @return array
     */
    public function filterElementIdByQueryDataProvider(): array
    {
        return [
            [['user1'], ['user1', 'user2', 'user3', 'user4'], 'user1@crafttest.com', true, 1, false],

            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user', true, 1, false],
            [['user4', 'user1', 'user2', 'user3'], [], 'user', true, 1, false],
            [['user1', 'user2', 'user3'], ['user1', 'user2', 'user3'], 'user', true, 1, false],
            [['user4'], ['user1', 'user2', 'user3', 'user4'], 'user someemail', true, 1, false],
            [[], ['user1', 'user2', 'user3'], 'user someemail', true, 1, false],

            // This should work. If you want an empty slug you should try: -slug:*
            [[], ['user1', 'user2', 'user3', 'user4'], 'slug:', true, 1, false],
            [[], ['user1', 'user2', 'user3', 'user4'], 'slug:""', true, 1, false],

            // User4 goes first as it has both user and someemail keywords
            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user OR someemail', true, 1, false],
            [['user4', 'user1'], ['user1', 'user2', 'user3', 'user4'], 'someemail OR -firstname:*', true, 1, false],
        ];
    }

    /**
     * @return array
     */
    public function filterScoresDataProvider(): array
    {
        return [
            [
                [
                    ['identifier' => 'user1', 'score' => 14.102564102564102]
                ], ['user1'], 'user', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 118.33333333333333],
                    ['identifier' => 'user1', 'score' => 14.102564102564102],
                    ['identifier' => 'user2', 'score' => 13.333333333333332],
                    ['identifier' => 'user3', 'score' => 13.333333333333332]
                ], ['user1', 'user2', 'user3', 'user4'], 'user', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 118.33333333333333],
                    ['identifier' => 'user1', 'score' => 14.102564102564102],
                    ['identifier' => 'user2', 'score' => 13.333333333333332],
                    ['identifier' => 'user3', 'score' => 13.333333333333332]
                ], [], 'user', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 1.6666666666666665],
                    ['identifier' => 'user1', 'score' => 0.0],
                ], ['user1', 'user2', 'user3', 'user4'], 'someemail OR -firstname:*', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 60.833333333333336],
                    ['identifier' => 'user1', 'score' => 7.051282051282051],
                    ['identifier' => 'user2', 'score' => 6.666666666666666],
                    ['identifier' => 'user3', 'score' => 6.666666666666666]
                ], ['user1', 'user2', 'user3', 'user4'], 'user OR someemail', true, 1
            ],
        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        parent::_before();

        $this->search = Craft::$app->getSearch();
    }

    /**
     * @inheritDoc
     */
    protected function _after()
    {
        parent::_after();

        // Because MyISAM doesn't support transactions we delete all search index elements except for user with id 1.
        // (The admin user created during test setup)
        Craft::$app->getDb()->createCommand()
            ->delete(
                Table::SEARCHINDEX,
                ['not', ['elementId' => 1]]
            )->execute();
    }

    // Private Methods
    // =========================================================================

    /**
     * @param $attributeName
     * @param $searchIndex
     * @return string
     */
    private function _getSearchIndexValueByAttribute($attributeName, $searchIndex): string
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
     * @param bool $typecastToInt
     * @return array
     */
    private function _usernameEmailArrayToIdList(array $usernameOrEmails, bool $typecastToInt = true): array
    {
        $ids = [];

        foreach ($usernameOrEmails as $usernameOrEmail) {
            $userId = $this->_getUserIdByEmailOrUserName($usernameOrEmail)->id;
            $ids[] = $typecastToInt === true ? (int)$userId : $userId;
        }

        return $ids;
    }

    /**
     * @param array $usernameOrEmailsAndScores
     * @return array
     */
    private function _scoreList(array $usernameOrEmailsAndScores): array
    {
        $ids = [];

        foreach ($usernameOrEmailsAndScores as $usernameOrEmailAndScore) {
            $userId = $this->_getUserIdByEmailOrUserName($usernameOrEmailAndScore['identifier'])->id;
            $ids[$userId] = $usernameOrEmailAndScore['score'];
        }

        return $ids;
    }

    /**
     * @param string $emailOrUsername
     * @return User|null
     */
    private function _getUserIdByEmailOrUserName(string $emailOrUsername): ?User
    {
        return Craft::$app->getUsers()->getUserByUsernameOrEmail($emailOrUsername);
    }
}
