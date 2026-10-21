<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\db\Table;
use craft\elements\Asset;
use craft\enums\PropagationMethod;
use craft\helpers\Db;
use craft\models\Volume;
use craft\queue\jobs\PropagateElements;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use crafttests\fixtures\SitesFixture;
use ReflectionMethod;
use UnitTester;

/**
 * Unit tests for volume propagation methods
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class AssetPropagationTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    private Volume $volume;

    private Volume $customVolume;

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

    protected function _before(): void
    {
        parent::_before();
        $this->volume = Craft::$app->getVolumes()->getVolumeByHandle('testVolume1');
        $this->customVolume = Craft::$app->getVolumes()->getVolumeByHandle('testVolume4');
    }

    protected function _after(): void
    {
        $this->volume->propagationMethod = PropagationMethod::All;
        $this->customVolume->propagationMethod = PropagationMethod::All;
        parent::_after();
    }

    public function testVolumeConfig(): void
    {
        self::assertSame('all', $this->volume->getConfig()['propagationMethod']);

        $volume = new Volume(['propagationMethod' => 'custom']);
        self::assertSame(PropagationMethod::Custom, $volume->propagationMethod);
        self::assertSame('custom', $volume->getConfig()['propagationMethod']);
    }

    public function testUnsupportedPropagationMethods(): void
    {
        foreach ([PropagationMethod::None, PropagationMethod::SiteGroup, PropagationMethod::Language] as $propagationMethod) {
            $volume = new Volume(['propagationMethod' => $propagationMethod]);
            $volume->validate(['propagationMethod']);
            self::assertTrue($volume->hasErrors('propagationMethod'), "{$propagationMethod->value} should be invalid");
        }

        foreach ([PropagationMethod::All, PropagationMethod::Custom] as $propagationMethod) {
            $volume = new Volume(['propagationMethod' => $propagationMethod]);
            $volume->validate(['propagationMethod']);
            self::assertFalse($volume->hasErrors('propagationMethod'), "{$propagationMethod->value} should be valid");
        }
    }

    public function testAllPropagation(): void
    {
        $asset = $this->createAsset(1000);

        self::assertEqualsCanonicalizing(Craft::$app->getSites()->getAllSiteIds(), $this->siteIdsForAsset($asset));
    }

    public function testCustomPropagation(): void
    {
        $this->volume->propagationMethod = PropagationMethod::Custom;
        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;

        // New assets should only be saved to the site they were created in
        $asset = $this->createAsset(1000);
        self::assertSame([1000], $this->siteIdsForAsset($asset));

        // Assets should still be found by ID from other sites
        $fetchedAsset = Craft::$app->getAssets()->getAssetById($asset->id);
        self::assertNotNull($fetchedAsset);
        self::assertSame(1000, $fetchedAsset->siteId);
        self::assertNull(Craft::$app->getAssets()->getAssetById($asset->id, $primarySiteId));

        // Assets can be added to other sites
        $asset = $fetchedAsset;
        $asset->setEnabledForSite([1000 => true, 1001 => true]);
        self::assertTrue(Craft::$app->getElements()->saveElement($asset), json_encode($asset->getErrors()));
        self::assertEqualsCanonicalizing([1000, 1001], $this->siteIdsForAsset($asset));

        // Switching back to “all” should propagate assets to the rest of the sites when they're resaved
        $this->volume->propagationMethod = PropagationMethod::All;
        $asset = Craft::$app->getAssets()->getAssetById($asset->id);
        self::assertTrue(Craft::$app->getElements()->saveElement($asset), json_encode($asset->getErrors()));
        self::assertEqualsCanonicalizing(Craft::$app->getSites()->getAllSiteIds(), $this->siteIdsForAsset($asset));
    }

    public function testNewSitePropagation(): void
    {
        $this->customVolume->propagationMethod = PropagationMethod::Custom;
        $primarySiteId = Craft::$app->getSites()->getPrimarySite()->id;
        $newSiteId = 1002;

        $allAsset = $this->createAsset($primarySiteId);
        $customAsset = $this->createAsset($primarySiteId, $this->customVolume);

        // Pretend the site was just added by removing the assets from it
        Db::delete(Table::ELEMENTS_SITES, ['elementId' => [$allAsset->id, $customAsset->id], 'siteId' => $newSiteId]);
        self::assertNotContains($newSiteId, $this->siteIdsForAsset($allAsset));
        self::assertSame([$primarySiteId], $this->siteIdsForAsset($customAsset));

        // Process the assets with the same job Sites::saveSite() queues for new sites
        $job = new PropagateElements([
            'elementType' => Asset::class,
            'siteId' => $newSiteId,
            'isNewSite' => true,
        ]);
        $processItem = new ReflectionMethod($job, 'processItem');
        $assets = Asset::find()->id([$allAsset->id, $customAsset->id])->siteId($primarySiteId)->all();
        self::assertCount(2, $assets);
        foreach ($assets as $asset) {
            $processItem->invoke($job, $asset);
        }

        self::assertContains($newSiteId, $this->siteIdsForAsset($allAsset));
        self::assertSame([$primarySiteId], $this->siteIdsForAsset($customAsset));
    }

    private function createAsset(int $siteId, ?Volume $volume = null): Asset
    {
        $volume ??= $this->volume;
        $tempPath = Craft::$app->getPath()->getTempPath() . '/' . uniqid('asset-propagation-') . '.png';
        copy(Craft::$app->getPath()->getTestsPath() . '/_craft/assets/shinybrad.png', $tempPath);

        $asset = new Asset();
        $asset->tempFilePath = $tempPath;
        $asset->setFilename(basename($tempPath));
        $asset->newFolderId = Craft::$app->getAssets()->getRootFolderByVolumeId($volume->id)->id;
        $asset->setVolumeId($volume->id);
        $asset->siteId = $siteId;
        $asset->avoidFilenameConflicts = true;
        $asset->setScenario(Asset::SCENARIO_CREATE);

        self::assertTrue(Craft::$app->getElements()->saveElement($asset), implode(', ', $asset->getFirstErrors()));

        return $asset;
    }

    private function siteIdsForAsset(Asset $asset): array
    {
        return Asset::find()
            ->id($asset->id)
            ->site('*')
            ->select('elements_sites.siteId')
            ->column();
    }
}
