<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\services;

use Craft;
use craft\elements\User;
use craft\test\TestCase;
use crafttests\fixtures\SitesFixture;
use UnitTester;

/**
 * Unit tests for the security service
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 4.9.0
 */
class SitesTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @return array
     */
    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider getSitesByGroupIdDataProvider
     * @param int $expectedCount
     * @param int $groupId
     */
    public function testGetSitesByGroupId(int $expectedCount, int $groupId): void
    {
        $sites = Craft::$app->getSites()->getSitesByGroupId($groupId);
        self::assertEquals($expectedCount, count($sites));
    }

    public function getSitesByGroupIdDataProvider(): array
    {
        return [
            [4, 1],
            [0, 9999],
        ];
    }

    /**
     *
     */
    public function testGetTotalSites(): void
    {
        self::assertEquals(4, Craft::$app->getSites()->getTotalSites());
    }

    /**
     *
     */
    public function testGetTotalEditableSites(): void
    {
        $userSession = Craft::$app->getUser();
        $sitesService = Craft::$app->getSites();
        $originalUser = $userSession->getIdentity(false);

        $userSession->setIdentity(null);
        $sitesService->refreshSites();
        self::assertEquals(0, Craft::$app->getSites()->getTotalEditableSites());

        $admin = User::find()->admin()->one();
        $userSession->setIdentity($admin);
        $sitesService->refreshSites();
        self::assertEquals(4, Craft::$app->getSites()->getTotalEditableSites());

        $userSession->setIdentity($originalUser);
        $sitesService->refreshSites();
    }

    /**
     * @dataProvider getSiteByIdDataProvider
     * @param bool $expectedNotEmpty
     * @param int $id
     */
    public function testGetSiteById(bool $expectedNotEmpty, int $id): void
    {
        $sites = Craft::$app->getSites()->getSiteById($id);
        self::assertEquals($expectedNotEmpty, !empty($sites));
    }

    /**
     * @return array
     */
    public function getSiteByIdDataProvider(): array
    {
        return [
            [true, 1000],
            [true, 1001],
            [true, 1002],
            [false, 999999],
        ];
    }

    /**
     * @dataProvider getSiteByHandleDataProvider
     * @param bool $expectedNotEmpty
     * @param string $handle
     */
    public function testGetSiteByHandle(bool $expectedNotEmpty, string $handle): void
    {
        $sites = Craft::$app->getSites()->getSiteByHandle($handle);
        self::assertEquals($expectedNotEmpty, !empty($sites));
    }

    /**
     * @return array
     */
    public function getSiteByHandleDataProvider(): array
    {
        return [
            [true, 'testSite1'],
            [true, 'testSite2'],
            [true, 'testSite3'],
            [false, 'fakeSiteHandle'],
        ];
    }

    /**
     * @dataProvider getSitesByLanguageDataProvider
     * @param int $expectedCount
     * @param string $language
     */
    public function testGetSitesByLanguage(int $expectedCount, string $language): void
    {
        $sites = Craft::$app->getSites()->getSitesByLanguage($language);
        self::assertEquals($expectedCount, count($sites));
    }

    /**
     * @return array
     */
    public function getSitesByLanguageDataProvider(): array
    {
        return [
            [2, 'en-US'],
            [2, 'nl'],
            [0, 'en-us'],
            [0, 'en'],
        ];
    }
}
