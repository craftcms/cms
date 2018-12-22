<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */
namespace craftunit\services;

use Codeception\Test\Unit;
use craft\db\Query;
use craft\elements\Entry;
use craft\elements\User;
use craftunit\fixtures\EntriesFixture;
use craftunit\fixtures\SitesFixture;
use craftunit\fixtures\UsersFixture;

/**
 * Unit tests for SearchServiceTest
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class SearchServiceTest extends Unit
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
     * @param $result
     * @param $elementIds
     * @param $query
     * @param bool $scoreResults
     * @param null $siteId
     * @param bool $returnScores
     */
    public function testFilterElementIdsByQuery($usernameOrEmailsForResult, $usernameOrEmailsForQuery, $query, $scoreResults = true, $siteId = null, $returnScores = false)
    {
        // Repackage the given emails/username into
        $result = $this->usernameEmailArrayToIdList($usernameOrEmailsForResult);
        $forQuery = $this->usernameEmailArrayToIdList($usernameOrEmailsForQuery);

        $filtered = \Craft::$app->getSearch()->filterElementIdsByQuery($forQuery, $query, $scoreResults, $siteId, $returnScores);

        $this->assertSame($result, $filtered);
    }

    /**
     * Provide an array with input usernames
     * @return array
     */
    public function filterElementIdByQueryData()
    {
        return [
            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user', true, 1, false],
            [['user4', 'user1', 'user2', 'user3'], [], 'user', true, 1, false],
            [['user1', 'user2', 'user3'], ['user1', 'user2', 'user3'], 'user', true, 1, false],
            [['user4'], ['user1', 'user2', 'user3', 'user4'], 'user someemail', true, 1, false],
            [[], ['user1', 'user2', 'user3'], 'user someemail', true, 1, false ],

            // User4 goes first as it has both user and someemail keywords
            [['user4', 'user1', 'user2', 'user3'], ['user1', 'user2', 'user3', 'user4'], 'user OR someemail', true, 1, false],
            // TODO: [[], ['user1', 'user2', 'user3', 'user4'],'user OR someemail', false, 1, false],
            [['user4', 'user1'], ['user1', 'user2', 'user3', 'user4'],'someemail OR -firstName:*', true, 1, false],
        ];
    }

    /**
     * @dataProvider filterScoresData
     * @param $result
     * @param $elementIds
     * @param $query
     * @param bool $scoreResults
     * @param null $siteId
     * @param bool $returnScores
     */
    public function testFilterScores($scoresAndNames, $usernameOrEmailsForQuery, $query, $scoreResults = true, $siteId = null)
    {
        // Repackage the given emails/username into
        $result = $this->scoreList($scoresAndNames, true, true);
        $forQuery = $this->usernameEmailArrayToIdList($usernameOrEmailsForQuery);

        $filtered = \Craft::$app->getSearch()->filterElementIdsByQuery($forQuery, $query, $scoreResults, $siteId, true);

        $this->assertSame($result, $filtered);
    }
    public function filterScoresData()
    {
        return [
            [[['identifier' => 'user1', 'score' => 13.333333333333332]], ['user1'], 'user', true, 1],
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

        ];
    }

    public function testElementQueryReturnsInt()
    {
        $result = $this->usernameEmailArrayToIdList(['user1', 'user2', 'user3'], true);
        $forQuery = $this->usernameEmailArrayToIdList(['user1', 'user2', 'user3'], false);

        $filtered = \Craft::$app->getSearch()->filterElementIdsByQuery($forQuery, 'user');

        $this->assertSame($result, $filtered);
    }

    private function usernameEmailArrayToIdList(array $usernameOrEmails, bool $typecastToInt = true)
    {
        $ids = [];
        foreach ($usernameOrEmails as $usernameOrEmail) {
            $userId = $this->getUserIdByEmailOrUserName($usernameOrEmail)->id;
            $ids[] = $typecastToInt === true ? (int)$userId : $userId;

        }

        return $ids;
    }

    private function scoreList(array $usernameOrEmailsAndScores)
    {
        $ids = [];
        foreach ($usernameOrEmailsAndScores as $usernameOrEmailAndScore) {
            $userId = $this->getUserIdByEmailOrUserName($usernameOrEmailAndScore['identifier'])->id;
            $ids[$userId] = $usernameOrEmailAndScore['score'];
        }

        return $ids;
    }

    private function getUserIdByEmailOrUserName(string $emailOrUsername)
    {
        return \Craft::$app->getUsers()->getUserByUsernameOrEmail($emailOrUsername);
    }
}