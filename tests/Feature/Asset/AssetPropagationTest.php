<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Data\Volume;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Models\Asset as AssetModel;
use CraftCms\Cms\Asset\Models\Volume as VolumeModel;
use CraftCms\Cms\Asset\Models\VolumeFolder as VolumeFolderModel;
use CraftCms\Cms\Asset\Volumes;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Actions\DeleteForSite;
use CraftCms\Cms\Element\ElementHelper;
use CraftCms\Cms\Element\Enums\PropagationMethod;
use CraftCms\Cms\Element\Jobs\PropagateElements;
use CraftCms\Cms\Element\Jobs\ResaveElements;
use CraftCms\Cms\ProjectConfig\Events\ItemUpdated;
use CraftCms\Cms\Site\Data\Site as SiteData;
use CraftCms\Cms\Site\Models\Site;
use CraftCms\Cms\Support\Facades\Assets;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Ui\Controls\ElementSites;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    actingAs(User::findOne());

    config()->set('filesystems.disks.asset-propagation-test', [
        'driver' => 'local',
        'root' => storage_path('framework/testing/asset-propagation-test'),
    ]);

    $this->primarySite = Sites::getPrimarySite();
    $this->secondSite = Site::factory()->create(['handle' => 'second', 'name' => 'Second Site']);
    Sites::refreshSites();
});

function assetPropagationVolume(PropagationMethod $propagationMethod): Volume
{
    $model = VolumeModel::factory()->create([
        'fs' => 'asset-propagation-test',
        'propagationMethod' => $propagationMethod->value,
    ]);

    app(Volumes::class)->reset();

    return app(Volumes::class)->getVolumeById($model->id);
}

function assetPropagationAsset(Volume $volume): Asset
{
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);

    expect(Elements::saveElement($asset))->toBeTrue();

    return $asset;
}

/** @return list<int> */
function assetPropagationSiteIds(Asset $asset): array
{
    return DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $asset->id)
        ->orderBy('siteId')
        ->pluck('siteId')
        ->all();
}

it('includes the propagation method in the volume config', function () {
    $volume = new Volume(['propagationMethod' => PropagationMethod::All]);

    expect($volume->getConfig()['propagationMethod'])->toBe('all')
        ->and(new Volume()->propagationMethod)->toBe(PropagationMethod::Custom);
});

it('only allows the all and custom propagation methods', function (PropagationMethod $propagationMethod, bool $valid) {
    $volume = new Volume([
        'name' => 'Test Volume',
        'handle' => 'testVolume',
        'fsHandle' => 'asset-propagation-test',
        'propagationMethod' => $propagationMethod,
    ]);

    expect($volume->validate())->toBe($valid);
})->with([
    'all' => [PropagationMethod::All, true],
    'custom' => [PropagationMethod::Custom, true],
    'none' => [PropagationMethod::None, false],
    'siteGroup' => [PropagationMethod::SiteGroup, false],
    'language' => [PropagationMethod::Language, false],
]);

it('saves assets to all sites in volumes that propagate to all sites', function () {
    $asset = assetPropagationAsset(assetPropagationVolume(PropagationMethod::All));

    expect(assetPropagationSiteIds($asset))->toBe([$this->primarySite->id, $this->secondSite->id]);
});

it('only saves assets to the sites they are assigned to in volumes with custom propagation', function () {
    $asset = assetPropagationAsset(assetPropagationVolume(PropagationMethod::Custom));

    expect(assetPropagationSiteIds($asset))->toBe([$this->primarySite->id]);

    $asset->setEnabledForSite([
        $this->primarySite->id => true,
        $this->secondSite->id => true,
    ]);

    expect(Elements::saveElement($asset))->toBeTrue()
        ->and(assetPropagationSiteIds($asset))->toBe([$this->primarySite->id, $this->secondSite->id]);
});

it('finds assets by ID in other sites', function () {
    $volume = assetPropagationVolume(PropagationMethod::Custom);
    $folder = VolumeFolderModel::factory()->create(['volumeId' => $volume->id]);
    $asset = AssetModel::factory()->createElement([
        'volumeId' => $volume->id,
        'folderId' => $folder->id,
    ]);
    $asset->siteId = $this->secondSite->id;
    expect(Elements::saveElement($asset))->toBeTrue();
    DB::table(Table::ELEMENTS_SITES)
        ->where('elementId', $asset->id)
        ->where('siteId', $this->primarySite->id)
        ->delete();

    expect(Assets::getAssetById($asset->id)?->siteId)->toBe($this->secondSite->id)
        ->and(Assets::getAssetById($asset->id, $this->primarySite->id))->toBeNull();
});

it('deletes the site-specific asset data when an asset is deleted for a site', function () {
    $asset = assetPropagationAsset(assetPropagationVolume(PropagationMethod::Custom));
    $asset->setEnabledForSite([
        $this->primarySite->id => true,
        $this->secondSite->id => true,
    ]);
    expect(Elements::saveElement($asset))->toBeTrue();

    $siteAsset = Assets::getAssetById($asset->id, $this->secondSite->id);
    $siteAsset->alt = 'Second site alt text';
    expect(Elements::saveElement($siteAsset))->toBeTrue()
        ->and(DB::table(Table::ASSETS_SITES)->where('assetId', $asset->id)->where('siteId', $this->secondSite->id)->exists())->toBeTrue()
        ->and(ElementHelper::isMultiSite($siteAsset))->toBeTrue();

    Elements::deleteElementForSite($siteAsset);

    expect(assetPropagationSiteIds($asset))->toBe([$this->primarySite->id])
        ->and(DB::table(Table::ASSETS_SITES)->where('assetId', $asset->id)->where('siteId', $this->secondSite->id)->exists())->toBeFalse();
});

it('resaves assets when a volume is switched to save assets to all sites', function () {
    $volume = new Volume([
        'name' => 'Test Volume',
        'handle' => 'testVolume',
        'fsHandle' => 'asset-propagation-test',
        'propagationMethod' => PropagationMethod::Custom,
    ]);
    expect(app(Volumes::class)->saveVolume($volume))->toBeTrue();

    Bus::fake([ResaveElements::class]);

    $volume = app(Volumes::class)->getVolumeById($volume->id);
    $volume->propagationMethod = PropagationMethod::All;
    expect(app(Volumes::class)->saveVolume($volume))->toBeTrue();

    Bus::assertDispatched(ResaveElements::class);
});

it('keeps existing volumes’ propagation method when applying config saved before the setting existed', function () {
    $volume = assetPropagationVolume(PropagationMethod::Custom);
    $config = $volume->getConfig();
    unset($config['propagationMethod']);

    Bus::fake([ResaveElements::class]);

    app(Volumes::class)->handleChangedVolume(new ItemUpdated(
        path: "volumes.$volume->uid",
        newValue: $config,
        tokenMatches: [$volume->uid],
    ));

    expect(DB::table(Table::VOLUMES)->where('id', $volume->id)->value('propagationMethod'))
        ->toBe(PropagationMethod::Custom->value);
    Bus::assertNotDispatched(ResaveElements::class);
});

it('saves assets to all sites for new volumes from config saved before the setting existed, on multi-site installs', function () {
    $uid = Str::uuid()->toString();

    app(Volumes::class)->handleChangedVolume(new ItemUpdated(
        path: "volumes.$uid",
        newValue: [
            'name' => 'Legacy Volume',
            'handle' => 'legacyVolume',
            'fs' => 'asset-propagation-test',
            'sortOrder' => 1,
        ],
        tokenMatches: [$uid],
    ));

    expect(DB::table(Table::VOLUMES)->where('uid', $uid)->value('propagationMethod'))
        ->toBe(PropagationMethod::All->value);
});

it('only propagates assets in volumes that save assets to all sites to new sites', function () {
    $allVolume = assetPropagationVolume(PropagationMethod::All);
    assetPropagationVolume(PropagationMethod::Custom);

    Bus::fake([PropagateElements::class]);

    expect(Sites::saveSite(new SiteData([
        'groupId' => $this->primarySite->groupId,
        'name' => 'New Site',
        'handle' => 'newSite',
        'language' => 'en-US',
    ])))->toBeTrue();

    Bus::assertDispatched(function (PropagateElements $job) use ($allVolume) {
        $criteria = new ReflectionProperty($job, 'criteria')->getValue($job);

        return ($criteria['volumeId'] ?? null) === [$allVolume->id];
    });
});

it('offers the Delete for Site action for volumes with custom propagation', function (PropagationMethod $propagationMethod, bool $expected) {
    $volume = assetPropagationVolume($propagationMethod);

    $actionTypes = collect(Asset::actions("volume:$volume->uid"))
        ->map(fn ($action) => is_array($action) ? $action['type'] : (is_string($action) ? $action : $action::class));

    expect($actionTypes->contains(DeleteForSite::class))->toBe($expected);
})->with([
    'custom' => [PropagationMethod::Custom, true],
    'all' => [PropagationMethod::All, false],
]);

it('shows a Sites pane for assets on multi-site installs', function () {
    $sitesFieldHtml = new ReflectionMethod(Asset::class, 'sitesFieldHtml');
    $sitesNodes = new ReflectionMethod(Asset::class, 'sitesNodes');

    $allAsset = assetPropagationAsset(assetPropagationVolume(PropagationMethod::All));
    expect((string) $sitesFieldHtml->invoke($allAsset))->toContain('element-sites-pane')
        ->toContain("site-{$this->secondSite->id}-field")
        ->not->toContain('craft-switch');

    $asset = assetPropagationAsset(assetPropagationVolume(PropagationMethod::Custom));
    $html = (string) $sitesFieldHtml->invoke($asset);

    expect($html)->toContain('element-sites-pane')
        ->toContain("site-{$this->primarySite->id}-field sel")
        ->not->toContain("site-{$this->secondSite->id}-field")
        ->not->toContain('craft-switch');

    $control = $sitesNodes->invoke($asset)[0]->getControl();
    expect($control)->toBeInstanceOf(ElementSites::class)
        ->and($control->props()['withStatuses'])->toBeFalse()
        ->and(collect($control->props()['sites'])->pluck('id')->all())->toBe([$this->primarySite->id])
        ->and(collect($control->props()['additionalSites'])->pluck('id')->all())->toBe([$this->secondSite->id]);

    $asset->setEnabledForSite([
        $this->primarySite->id => true,
        $this->secondSite->id => true,
    ]);
    expect(Elements::saveElement($asset))->toBeTrue();

    $html = (string) $sitesFieldHtml->invoke(Assets::getAssetById($asset->id, $this->primarySite->id));
    expect($html)->toContain("site-{$this->secondSite->id}-field")
        ->toContain("site={$this->secondSite->handle}")
        ->not->toContain('element-sites-toggle');
});

it('only lists other sites within a disclosure when there are more than 10 sites', function () {
    $sitesFieldHtml = new ReflectionMethod(Asset::class, 'sitesFieldHtml');
    $asset = assetPropagationAsset(assetPropagationVolume(PropagationMethod::Custom));

    $saveToAllSites = function () use ($asset): Asset {
        $asset->setEnabledForSite(array_fill_keys(Sites::getAllSiteIds()->all(), true));
        expect(Elements::saveElement($asset))->toBeTrue();

        return Assets::getAssetById($asset->id, $this->primarySite->id);
    };

    // Bring the total number of sites up to 10
    $num = 0;
    while (Sites::getAllSiteIds()->count() < 10) {
        $num++;
        Site::factory()->create(['handle' => "extraSite$num", 'name' => "Extra Site $num"]);
        Sites::refreshSites();
    }

    // 10 sites should all be listed
    $html = (string) $sitesFieldHtml->invoke($saveToAllSites());

    foreach (Sites::getAllSiteIds() as $siteId) {
        expect($html)->toContain("site-$siteId-field");
    }

    expect($html)->not->toContain('element-sites-toggle')
        ->not->toContain('field hidden"');

    // 11 sites should list the other 10 within a disclosure, after the current site
    Site::factory()->create(['handle' => 'extraSite'.($num + 1), 'name' => 'Extra Site '.($num + 1)]);
    Sites::refreshSites();
    $html = (string) $sitesFieldHtml->invoke($saveToAllSites());

    expect($html)->toContain('>10 other sites</button>')
        ->not->toContain("site-{$this->primarySite->id}-field sel hidden");

    foreach (Sites::getAllSiteIds()->reject(fn (int $siteId) => $siteId === $this->primarySite->id) as $siteId) {
        expect($html)->toContain("site-$siteId-field hidden");
    }

    expect(strpos($html, "site-{$this->primarySite->id}-field sel"))->toBeLessThan(strpos($html, 'element-sites-toggle'));
});
