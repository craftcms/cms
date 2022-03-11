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
use crafttests\fixtures\UserFixture;
use UnitTester;

/**
 * Unit tests for the search service
 *
 * @todo There are MySQL and PostgreSQL specific search tests that need to be performed.
 *
 * Searching and some of the commands run in this test are documented here:
 * https://craftcms.com/docs/3.x/searching.html
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class SearchTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @var Search
     */
    protected $search;

    public function _fixtures(): array
    {
        return [
            'users' => [
                'class' => UserFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider filterElementIdByQueryDataProvider
     *
     * @param $usernameOrEmailsForResult
     * @param $usernameOrEmailsForQuery
     * @param $searchQuery
     */
    public function testSearchElements($usernameOrEmailsForResult, $usernameOrEmailsForQuery, $searchQuery)
    {
        // Repackage the dataProvider data into something that can be used by the filter function
        $result = $this->_usernameEmailArrayToIdList($usernameOrEmailsForResult);
        $elementIds = $this->_usernameEmailArrayToIdList($usernameOrEmailsForQuery);
        $elementQuery = User::find()
            ->id($elementIds ?: null)
            ->search($searchQuery);

        // Filter them
        $filtered = array_keys($this->search->searchElements($elementQuery));

        sort($result, SORT_NUMERIC);
        sort($filtered, SORT_NUMERIC);

        self::assertSame($result, $filtered);
    }

    /**
     * @dataProvider filterElementIdByQueryDataProvider
     *
     * @param $usernameOrEmailsForResult
     * @param $usernameOrEmailsForQuery
     * @param $searchQuery
     */
    public function testFilterElementIdsByQuery($usernameOrEmailsForResult, $usernameOrEmailsForQuery, $searchQuery)
    {
        // Repackage the dataProvider data into something that can be used by the filter function
        $result = $this->_usernameEmailArrayToIdList($usernameOrEmailsForResult);
        $elementIds = $this->_usernameEmailArrayToIdList($usernameOrEmailsForQuery);

        // Filter them
        $filtered = $this->search->filterElementIdsByQuery($elementIds, $searchQuery, true, 1, false);

        sort($result, SORT_NUMERIC);
        sort($filtered, SORT_NUMERIC);

        self::assertSame($result, $filtered);
    }

    /**
     *
     */
    public function testElementQueryReturnsInt()
    {
        $result = $this->_usernameEmailArrayToIdList(['user1', 'user2', 'user3'], true);
        $forQuery = $this->_usernameEmailArrayToIdList(['user1', 'user2', 'user3'], false);

        $filtered = $this->search->filterElementIdsByQuery($forQuery, 'user');

        self::assertSame($result, $filtered);
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

        self::assertSame(' testindexelementattributes1 test com ', $this->_getSearchIndexValueByAttribute('email', $searchIndex));
        self::assertSame(' john smith ', $this->_getSearchIndexValueByAttribute('firstname', $searchIndex));
        self::assertSame(' wil k er son ', $this->_getSearchIndexValueByAttribute('lastname', $searchIndex));
        self::assertSame(' john smith wil k er son ', $this->_getSearchIndexValueByAttribute('fullname', $searchIndex));
    }

    /**
     * Provide an array with input user names
     *
     * @return array
     */
    public function filterElementIdByQueryDataProvider(): array
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
     * @param string $emailOrUsername
     * @return User|null
     */
    private function _getUserIdByEmailOrUserName(string $emailOrUsername): ?User
    {
        return Craft::$app->getUsers()->getUserByUsernameOrEmail($emailOrUsername);
    }
}
