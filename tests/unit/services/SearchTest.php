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
use craft\test\TestCase;
use crafttests\fixtures\UserFixture;

/**
 * Unit tests for the search service
 *
 * @todo There are MySQL and PostgreSQL specific search tests that need to be performed.
 *
 * Searching and some of the commands run in this test are documented here:
 * https://craftcms.com/docs/4.x/searching.html
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

    /**
     * Provide an array with input user names
     *
     * @return array
     */
    public function searchElementsDataProvider(): array
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
     * @inheritDoc
     */
    protected function _after(): void
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
     * @param bool $typecastToInt
     * @return array
     */
    private function _usernameEmailArrayToIdList(array $usernameOrEmails, bool $typecastToInt = true): array
    {
        $ids = [];
        $usersService = Craft::$app->getUsers();

        foreach ($usernameOrEmails as $usernameOrEmail) {
            $userId = $usersService->getUserByUsernameOrEmail($usernameOrEmail)->id;
            $ids[] = $typecastToInt === true ? (int)$userId : $userId;
        }

        return $ids;
    }
}
