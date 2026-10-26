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
use craft\helpers\ArrayHelper;
use craft\helpers\ElementHelper;
use craft\models\Site;
use craft\models\Volume;
use craft\queue\jobs\PropagateElements;
use craft\queue\QueueInterface;
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

        // New volumes should default to custom propagation, but the temporary volume should save to all sites
        self::assertSame(PropagationMethod::Custom, (new Volume())->propagationMethod);
        self::assertSame(PropagationMethod::All, Craft::$app->getVolumes()->getTemporaryVolume()->propagationMethod);

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

        // The UI shouldn't offer to delete assets for the only site they exist in
        $site1Asset = Craft::$app->getAssets()->getAssetById($asset->id, 1000);
        self::assertFalse(ElementHelper::isMultiSite($site1Asset));

        // ...but if it happens anyway, they should be soft-deleted, like a normal asset deletion
        self::assertTrue($elementsService->canDeleteForSite($site1Asset, $admin));
        self::assertFalse($elementsService->canDeleteForSite($site1Asset, new User()));
        $elementsService->deleteElementForSite($site1Asset);
        self::assertSame([], $this->siteIdsForAsset($asset));
        $trashedAsset = Asset::find()->id($asset->id)->siteId(1000)->trashed()->one();
        self::assertNotNull($trashedAsset);
        self::assertNotNull($trashedAsset->dateDeleted);

        // Assets in volumes that propagate to all sites can't be deleted for a site
        $this->volume->propagationMethod = PropagationMethod::All;
        $allAsset = $this->createAsset(1000);
        self::assertFalse($elementsService->canDeleteForSite($allAsset, $admin));
    }

    public function testSitesField(): void
    {
        $sitesFieldHtml = new ReflectionMethod(Asset::class, 'sitesFieldHtml');
        Craft::$app->getUser()->setIdentity(User::findOne(1));

        // Assets in volumes that propagate to all sites should list all of the sites
        $allAsset = Craft::$app->getAssets()->getAssetById($this->createAsset(1000)->id, 1000);
        $html = $sitesFieldHtml->invoke($allAsset);
        self::assertStringContainsString('element-sites-pane', $html);
        self::assertStringContainsString('site-1001-field', $html);
        self::assertStringNotContainsString('lightswitch', $html);

        // Assets that can be added to other sites should list the sites they're saved in
        $this->volume->propagationMethod = PropagationMethod::Custom;
        $asset = Craft::$app->getAssets()->getAssetById($this->createAsset(1000)->id, 1000);
        $html = $sitesFieldHtml->invoke($asset);
        self::assertStringContainsString('element-sites-pane', $html);
        self::assertStringContainsString('site-1000-field', $html);
        self::assertStringNotContainsString('site-1001-field', $html);
        self::assertStringContainsString('site-1000-field sel', $html);
        self::assertStringNotContainsString('element-sites-toggle', $html);
        self::assertStringNotContainsString('class="status', $html);

        // Sites other than the current one should link to the asset in that site
        $asset->setEnabledForSite([1000 => true, 1001 => true]);
        self::assertTrue(Craft::$app->getElements()->saveElement($asset), json_encode($asset->getErrors()));
        $html = $sitesFieldHtml->invoke(Craft::$app->getAssets()->getAssetById($asset->id, 1000));
        self::assertStringContainsString('site-1001-field', $html);
        self::assertStringContainsString('site=testSite2', $html);
        self::assertStringNotContainsString('site=testSite1', $html);

        // A single other site shouldn't be within a disclosure
        self::assertStringNotContainsString('element-sites-toggle', $html);
        self::assertStringNotContainsString('field hidden"', $html);

        // The pane should still be shown once the asset has been saved to all sites
        $asset = Craft::$app->getAssets()->getAssetById($asset->id, 1000);
        $asset->setEnabledForSite(array_fill_keys(Craft::$app->getSites()->getAllSiteIds(), true));
        self::assertTrue(Craft::$app->getElements()->saveElement($asset), json_encode($asset->getErrors()));
        self::assertEqualsCanonicalizing(Craft::$app->getSites()->getAllSiteIds(), $this->siteIdsForAsset($asset));
        $html = $sitesFieldHtml->invoke(Craft::$app->getAssets()->getAssetById($asset->id, 1000));
        self::assertStringContainsString('element-sites-pane', $html);
        self::assertStringContainsString('site=testSite3', $html);

        // 10 or fewer sites shouldn't be within a disclosure
        self::assertStringNotContainsString('element-sites-toggle', $html);
        self::assertStringNotContainsString('field hidden"', $html);
    }

    public function testSitesFieldCollapsesOtherSitesWhenThereAreMoreThanTen(): void
    {
        $sitesFieldHtml = new ReflectionMethod(Asset::class, 'sitesFieldHtml');
        Craft::$app->getUser()->setIdentity(User::findOne(1));
        $sitesService = Craft::$app->getSites();
        $this->volume->propagationMethod = PropagationMethod::Custom;
        $asset = Craft::$app->getAssets()->getAssetById($this->createAsset(1000)->id, 1000);

        // Bring the total number of sites up to 10
        $newSiteIds = [];
        while (count($sitesService->getAllSiteIds()) < 10) {
            $newSiteIds[] = $this->createSite(count($newSiteIds) + 1)->id;
        }

        $saveToAllSites = function() use (&$asset) {
            $asset->setEnabledForSite(array_fill_keys(Craft::$app->getSites()->getAllSiteIds(), true));
            self::assertTrue(Craft::$app->getElements()->saveElement($asset), json_encode($asset->getErrors()));
            $asset = Craft::$app->getAssets()->getAssetById($asset->id, 1000);
        };

        // 10 sites should all be listed
        $saveToAllSites();
        $html = $sitesFieldHtml->invoke($asset);
        foreach ($sitesService->getAllSiteIds() as $siteId) {
            self::assertStringContainsString("site-$siteId-field", $html);
        }
        self::assertStringNotContainsString('element-sites-toggle', $html);
        self::assertStringNotContainsString('field hidden"', $html);

        // 11 sites should list the other 10 within a disclosure, after the current site
        $newSiteIds[] = $this->createSite(count($newSiteIds) + 1)->id;
        $saveToAllSites();
        $html = $sitesFieldHtml->invoke($asset);
        self::assertStringContainsString('>10 other sites</button>', $html);
        self::assertStringNotContainsString('site-1000-field sel hidden', $html);
        foreach (ArrayHelper::withoutValue($sitesService->getAllSiteIds(), 1000) as $siteId) {
            self::assertStringContainsString("site-$siteId-field hidden", $html);
        }
        self::assertLessThan(strpos($html, 'element-sites-toggle'), strpos($html, 'site-1000-field sel'));
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

    private function createSite(int $num): Site
    {
        $sitesService = Craft::$app->getSites();
        $site = new Site([
            'groupId' => $sitesService->getPrimarySite()->groupId,
            'name' => "Extra site $num",
            'handle' => "extraSite$num",
            'language' => 'en-US',
        ]);
        self::assertTrue($sitesService->saveSite($site), json_encode($site->getErrors()));
        return $site;
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
