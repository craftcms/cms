<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements\db;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Entry;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use crafttests\fixtures\SitesFixture;
use UnitTester;
use yii\base\NotSupportedException;

/**
 * Unit tests for ElementQuery
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.6.11
 */
class ElementQueryTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'sites' => [
                'class' => SitesFixture::class,
            ],
            'assets' => [
                'class' => AssetFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider relatedToDataProvider
     * @param array|false $expected
     * @param mixed $relatedToParam
     * @param mixed $andRelatedToParam
     */
    public function testAndRelatedTo(array|false $expected, mixed $relatedToParam, mixed $andRelatedToParam): void
    {
        if ($expected === false) {
            self::expectException(NotSupportedException::class);
        }

        $query = Entry::find()
            ->relatedTo($relatedToParam)
            ->andRelatedTo($andRelatedToParam);

        self::assertEquals($expected, $query->relatedTo);
    }

    /**
     * @dataProvider normalizeOrderByDataProvider
     * @param array $expected
     * @param mixed $columns
     * @return void
     */
    public function testNormalizeOrderBy(array $expected, mixed $columns): void
    {
        $query = Entry::find();
        self::assertSame($expected, $this->invokeMethod($query, 'normalizeOrderBy', [$columns]));
    }

    public function testUniquePreferSites(): void
    {
        $db = Craft::$app->getDb();
        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;
        $assetIds = Asset::find()
            ->site('*')
            ->unique()
            ->filename(['shinybrad.png', 'shinybrad2.png'])
            ->orderBy(['elements.id' => SORT_ASC])
            ->ids();
        self::assertCount(2, $assetIds);
        [$assetA, $assetB] = $assetIds;
        self::assertEqualsCanonicalizing(
            Craft::$app->getSites()->getAllSiteIds(),
            Asset::find()->id($assetB)->site('*')->select('elements_sites.siteId')->column(),
        );

        // Asset B: missing from site 1001, and its site 1000 row won't match the title
        // filter below, so the only rows left are for the primary site and site 1002
        $db->createCommand()->delete(Table::ELEMENTS_SITES, ['elementId' => $assetB, 'siteId' => 1001])->execute();
        $db->createCommand()->update(Table::ELEMENTS_SITES, ['title' => 'Excluded'], ['elementId' => $assetB, 'siteId' => 1000])->execute();
        $fallbackSiteId = (int)(new Query())
            ->select('siteId')
            ->from(Table::ELEMENTS_SITES)
            ->where(['elementId' => $assetB, 'siteId' => [$primarySiteId, 1002]])
            ->orderBy(['id' => SORT_ASC])
            ->scalar();

        $query = fn() => Asset::find()
            ->id($assetIds)
            ->site('*')
            ->unique()
            ->preferSites([1001, 'testSite1'])
            ->orderBy(['elements.id' => SORT_ASC])
            ->title('not Excluded');

        $sitesById = fn(array $assets) => array_map(fn(Asset $asset) => [$asset->id, $asset->siteId], $assets);

        self::assertSame([[$assetA, 1001], [$assetB, $fallbackSiteId]], $sitesById($query()->all()));
        self::assertSame(2, (int)$query()->count());
        // limit and offset apply to unique elements, after each one's site is chosen
        self::assertSame([[$assetA, 1001]], $sitesById($query()->limit(1)->all()));
        self::assertSame([[$assetB, $fallbackSiteId]], $sitesById($query()->offset(1)->limit(1)->all()));
        // without the title filter, asset B's site 1000 row is preferred again
        self::assertSame([[$assetA, 1001], [$assetB, 1000]], $sitesById($query()->title(null)->all()));
    }

    /**
     * @return array
     */
    public static function relatedToDataProvider(): array
    {
        return [
            [false, ['or', ['targetElement' => 1], ['targetElement' => 2]], 3],
            [['or', 1, 2], null, ['or', 1, 2]],
            [['and', ['field' => null, 'sourceSite' => null, 'element' => ['or', 1]], ['field' => null, 'sourceSite' => null, 'element' => ['or', 1, 2]]], ['or', 1], [1, 2]],
        ];
    }

    /**
     * @return array
     */
    public static function normalizeOrderByDataProvider(): array
    {
        return [
            [['score' => SORT_DESC], 'score'],
            [['score' => SORT_DESC], ' score '],
            [['score' => SORT_ASC], 'score asc'],
            [['score' => SORT_ASC], ' score asc '],
            [['foo' => SORT_ASC, 'score' => SORT_DESC, 'bar' => SORT_ASC], 'foo, score, bar'],
            [['foo' => SORT_DESC, 'score' => SORT_ASC, 'bar' => SORT_DESC], 'foo desc, score asc, bar desc'],
        ];
    }
}
