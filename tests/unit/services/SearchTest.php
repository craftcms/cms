<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\services;

use Codeception\Test\Unit;
use Craft;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craftunit\fixtures\EntriesFixture;
use craftunit\fixtures\SitesFixture;
use craftunit\fixtures\UsersFixture;

/**
 * Unit tests for SearchServiceTest
 *
 * TODO: 1. Are these tests understandable? 2. What other search scenarios/edge-cases might need testing?
 *
 * Searching and some of the commands run in this test are documented here:
 * https://docs.craftcms.com/v3/searching.html#supported-syntaxes
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class SearchTest extends Unit
{
    public function _fixtures()
    {
        return [
            'users' => [
              'class' => UsersFixture::class,
            ]
        ];
    }


    /**
     * @dataProvider filterElementIdByQueryData
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
        $result = $this->usernameEmailArrayToIdList($usernameOrEmailsForResult);
        $forQuery = $this->usernameEmailArrayToIdList($usernameOrEmailsForQuery);

        // Filter them
        $filtered = Craft::$app->getSearch()->filterElementIdsByQuery($forQuery, $query, $scoreResults, $siteId, $returnScores);

        $this->assertSame($result, $filtered);
    }

    /**
     * Provide an array with input usernames
     *
     * @return array
     */
    public function filterElementIdByQueryData()
    {
        return [
            [['user1'], ['user1', 'user2', 'user3', 'user4'], 'user1@crafttest.com', true, 1, false],

            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user', true, 1, false],
            [['user4', 'user1', 'user2', 'user3'], [], 'user', true, 1, false],
            [['user1', 'user2', 'user3'], ['user1', 'user2', 'user3'], 'user', true, 1, false],
            [['user4'], ['user1', 'user2', 'user3', 'user4'], 'user someemail', true, 1, false],
            [[], ['user1', 'user2', 'user3'], 'user someemail', true, 1, false ],

            // This should work. If you want an empty slug you should try: -slug:*
            [[], ['user1', 'user2', 'user3', 'user4'], 'slug:', true, 1, false],
            [[], ['user1', 'user2', 'user3', 'user4'], 'slug:""', true, 1, false],

            // User4 goes first as it has both user and someemail keywords
            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user OR someemail', true, 1, false],
            [['user4', 'user1'], ['user1', 'user2', 'user3', 'user4'],'someemail OR -firstName:*', true, 1, false],
        ];
    }

    /**
     * @dataProvider filterScoresData
     * @param $scoresAndNames
     * @param $usernameOrEmailsForQuery
     * @param $query
     * @param bool $scoreResults
     * @param null $siteId
     */
    public function testFilterScores($scoresAndNames, $usernameOrEmailsForQuery, $query, $scoreResults = true, $siteId = null)
    {
        // Repackage the dataProvider input into what the filter funciton will return.
        $result = $this->scoreList($scoresAndNames, true, true);

        // Get the user ids to send into the filter function
        $forQuery = $this->usernameEmailArrayToIdList($usernameOrEmailsForQuery);

        // Filter them
        $filtered = Craft::$app->getSearch()->filterElementIdsByQuery($forQuery, $query, $scoreResults, $siteId, true);

        $this->assertSame($result, $filtered);
    }
    public function filterScoresData()
    {
        return [
            [
                [['identifier' => 'user1', 'score' => 13.333333333333332]
                ], ['user1'], 'user', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 118.33333333333333],
                    ['identifier' => 'user1', 'score' => 13.333333333333332],
                    ['identifier' => 'user2', 'score' => 13.333333333333332],
                    ['identifier' => 'user3', 'score' => 13.333333333333332]
                ], ['user1', 'user2', 'user3', 'user4'], 'user', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 118.33333333333333],
                    ['identifier' => 'user1', 'score' => 13.333333333333332],
                    ['identifier' => 'user2', 'score' => 13.333333333333332],
                    ['identifier' => 'user3', 'score' => 13.333333333333332]
                ], [], 'user', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 1.6666666666666665],
                    ['identifier' => 'user1', 'score' => 0.0],
                ],['user1', 'user2', 'user3', 'user4'],'someemail OR -firstName:*', true, 1
            ],
            [
                [
                    ['identifier' => 'user4', 'score' => 60.833333333333336],
                    ['identifier' => 'user1', 'score' => 6.666666666666666],
                    ['identifier' => 'user2', 'score' => 6.666666666666666],
                    ['identifier' => 'user3', 'score' => 6.666666666666666]
                ], ['user1', 'user2', 'user3', 'user4'], 'user OR someemail', true, 1
            ],
        ];
    }

    public function testElementQueryReturnsInt()
    {
        $result = $this->usernameEmailArrayToIdList(['user1', 'user2', 'user3'], true);
        $forQuery = $this->usernameEmailArrayToIdList(['user1', 'user2', 'user3'], false);

        $filtered = Craft::$app->getSearch()->filterElementIdsByQuery($forQuery, 'user');

        $this->assertSame($result, $filtered);
    }


    /*
     * Creates a new User(); and runs indexElementAttributes on it to see how its property values are stored in the database.
     * TODO: test with fields and multisite using entries
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
        Craft::$app->getSearch()->indexElementAttributes($user);

        // Get the data from the DB
        $searchIndex = (new Query())->select('*')->from('{{%searchindex}}')->where(['elementId' => $user->id])->all();

        $this->assertSame(' testindexelementattributes1 test com ', $this->getSearchIndexValueByAttribute('email', $searchIndex));
        $this->assertSame(' john smith ', $this->getSearchIndexValueByAttribute('firstname', $searchIndex));
        $this->assertSame(' wil k er son ', $this->getSearchIndexValueByAttribute('lastname', $searchIndex));
        $this->assertSame(' john smith wil k er son ', $this->getSearchIndexValueByAttribute('fullname', $searchIndex));
    }

    /**
     * @param $attributeName
     * @param $searchIndex
     * @return string
     */
    private function getSearchIndexValueByAttribute($attributeName, $searchIndex) : string
    {
        foreach (ArrayHelper::filterByValue($searchIndex, 'attribute', $attributeName) as $array) {
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
    private function usernameEmailArrayToIdList(array $usernameOrEmails, bool $typecastToInt = true)
    {
        $ids = [];
        foreach ($usernameOrEmails as $usernameOrEmail) {
            $userId = $this->getUserIdByEmailOrUserName($usernameOrEmail)->id;
            $ids[] = $typecastToInt === true ? (int)$userId : $userId;
        }

        return $ids;
    }

    /**
     * @param array $usernameOrEmailsAndScores
     * @return array
     */
    private function scoreList(array $usernameOrEmailsAndScores)
    {
        $ids = [];
        foreach ($usernameOrEmailsAndScores as $usernameOrEmailAndScore) {
            $userId = $this->getUserIdByEmailOrUserName($usernameOrEmailAndScore['identifier'])->id;
            $ids[$userId] = $usernameOrEmailAndScore['score'];
        }

        return $ids;
    }

    /**
     * @param string $emailOrUsername
     * @return User|null
     */
    private function getUserIdByEmailOrUserName(string $emailOrUsername)
    {
        return Craft::$app->getUsers()->getUserByUsernameOrEmail($emailOrUsername);
    }
}
