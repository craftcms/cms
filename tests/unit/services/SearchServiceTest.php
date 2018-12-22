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


        $filtered = \Craft::$app->getSearch()->filterElementIdsByQuery($forQuery, $query, $scoreResults, $siteId. $returnScores);

        $this->assertSame($result, $filtered);
    }
    public function filterElementIdByQueryData()
    {
        return [
            [
                ['user1', 'user2', 'user3'],
                ['user1', 'user2', 'user3'],
                'user',
                true,
                1
            ],
            [
                ['user4'],
                ['user1', 'user2', 'user3', 'user4'],
                'user someemail',
                true,
                1
            ]
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

    private function getUserIdByEmailOrUserName(string $emailOrUsername)
    {
        return \Craft::$app->getUsers()->getUserByUsernameOrEmail($emailOrUsername);
    }
}