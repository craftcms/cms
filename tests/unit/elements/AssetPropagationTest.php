<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\elements;

use Craft;
use craft\db\Query;
use craft\db\Table;
use craft\elements\actions\DeleteForSite;
use craft\elements\Asset;
use craft\elements\User;
use craft\enums\PropagationMethod;
use craft\models\Site;
use craft\models\Volume;
use craft\queue\jobs\PropagateElements;
use craft\queue\QueueInterface;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use crafttests\fixtures\SitesFixture;
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
        $sitesService = Craft::$app->getSites();
        $primarySiteId = $sitesService->getPrimarySite()->id;

        $allAsset = $this->createAsset($primarySiteId);
        $customAsset = $this->createAsset($primarySiteId, $this->customVolume);
        self::assertSame([$primarySiteId], $this->siteIdsForAsset($customAsset));

        $site = new Site([
            'groupId' => $sitesService->getPrimarySite()->groupId,
            'name' => 'New site',
            'handle' => 'newSite',
            'language' => 'en-US',
        ]);
        self::assertTrue($sitesService->saveSite($site), json_encode($site->getErrors()));

        // Only assets in volumes that propagate to all sites should be queried by the propagation job
        $job = $this->propagateElementsJob(Asset::class, $site->id);
        self::assertNotNull($job);
        $query = Asset::find();
        Craft::configure($query, $job->criteria);
        $assetIds = $query->ids();
        self::assertContains($allAsset->id, $assetIds);
        self::assertNotContains($customAsset->id, $assetIds);

        $queue = Craft::$app->getQueue();
        if (!$queue instanceof QueueInterface) {
            self::fail('The queue doesn’t implement ' . QueueInterface::class);
        }
        $queue->run();

        self::assertContains($site->id, $this->siteIdsForAsset($allAsset));
        self::assertSame([$primarySiteId], $this->siteIdsForAsset($customAsset));
    }

    public function testDeleteForSite(): void
    {
        $this->volume->propagationMethod = PropagationMethod::Custom;
        $elementsService = Craft::$app->getElements();
        $admin = User::findOne(1);
        self::assertNotNull($admin);

        $asset = $this->createAsset(1000);
        $asset = Craft::$app->getAssets()->getAssetById($asset->id, 1000);
        $asset->setEnabledForSite([1000 => true, 1001 => true]);
        self::assertTrue($elementsService->saveElement($asset), json_encode($asset->getErrors()));
        self::assertEqualsCanonicalizing([1000, 1001], $this->siteIdsForAsset($asset));

        $site2Asset = Craft::$app->getAssets()->getAssetById($asset->id, 1001);
        $site2Asset->alt = 'Site 2 alt text';
        self::assertTrue($elementsService->saveElement($site2Asset), json_encode($site2Asset->getErrors()));
        self::assertTrue((new Query())->from(Table::ASSETS_SITES)->where(['assetId' => $asset->id, 'siteId' => 1001])->exists());

        // Users need to be able to delete the asset
        self::assertTrue($elementsService->canDeleteForSite($site2Asset, $admin));
        self::assertFalse($elementsService->canDeleteForSite($site2Asset, new User()));

        $elementsService->deleteElementForSite($site2Asset);
        self::assertSame([1000], $this->siteIdsForAsset($asset));
        self::assertFalse((new Query())->from(Table::ASSETS_SITES)->where(['assetId' => $asset->id, 'siteId' => 1001])->exists());

        // Assets can't be deleted for the only site they exist in
        $site1Asset = Craft::$app->getAssets()->getAssetById($asset->id, 1000);
        self::assertFalse($elementsService->canDeleteForSite($site1Asset, $admin));

        // Assets in volumes that propagate to all sites can't be deleted for a site
        $this->volume->propagationMethod = PropagationMethod::All;
        $allAsset = $this->createAsset(1000);
        self::assertFalse($elementsService->canDeleteForSite($allAsset, $admin));
    }

    public function testDeleteForSiteAction(): void
    {
        Craft::$app->getUser()->setIdentity(User::findOne(1));
        $source = "volume:{$this->volume->uid}";

        self::assertNotContains(DeleteForSite::class, $this->actionTypes($source));

        $this->volume->propagationMethod = PropagationMethod::Custom;
        self::assertContains(DeleteForSite::class, $this->actionTypes($source));
    }

    private function actionTypes(string $source): array
    {
        return array_map(fn($action) => is_array($action) ? $action['type'] : (is_string($action) ? $action : get_class($action)), Asset::actions($source));
    }

    private function propagateElementsJob(string $elementType, int $siteId): ?PropagateElements
    {
        $jobs = (new Query())
            ->select(['job'])
            ->from(Table::QUEUE)
            ->column();

        foreach ($jobs as $job) {
            $job = Craft::$app->getQueue()->serializer->unserialize(is_resource($job) ? stream_get_contents($job) : $job);
            if ($job instanceof PropagateElements && $job->elementType === $elementType && $job->siteId === [$siteId]) {
                return $job;
            }
        }

        return null;
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
