<?php

declare(strict_types=1);

use craft\helpers\App;
use craft\web\AssetManager;
use CraftCms\Cms\Cms;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Facades\File;
use yii\web\AssetBundle;

uses(TestCase::class);

class PublishAssetBundlesCommandTestAsset extends AssetBundle
{
    public static string $directory;

    public $js = ['plugin.js'];

    public function init(): void
    {
        $this->sourcePath = self::$directory;
        parent::init();
    }
}

beforeEach(function() {
    $this->directory = storage_path('runtime/asset-bundles-' . uniqid());
    PublishAssetBundlesCommandTestAsset::$directory = "$this->directory/source";
    File::ensureDirectoryExists("$this->directory/source");
    File::put("$this->directory/source/plugin.js", 'window.plugin = true;');
});

afterEach(function() {
    File::deleteDirectory($this->directory);
});

function runtimeAssetManager(): AssetManager
{
    Cms::config()->publishAssetBundlesAtRuntime = false;

    return Craft::createObject(App::assetManagerConfig());
}

it('publishes asset bundles at build time where runtime URLs point when runtime publishing is disabled', function() {
    Cms::config()->resourceBasePath = "$this->directory/cpresources";

    $this->artisan('craft:asset-bundles:publish', ['class' => [PublishAssetBundlesCommandTestAsset::class]])
        ->assertSuccessful();

    // Deployments preserve paths, but not modification times
    touch("$this->directory/source/plugin.js", time() - 3600);
    touch("$this->directory/source", time() - 3600);

    $assetManager = runtimeAssetManager();
    $url = $assetManager->getAssetUrl($assetManager->getBundle(PublishAssetBundlesCommandTestAsset::class), 'plugin.js');
    $hash = basename(dirname(parse_url($url, PHP_URL_PATH)));

    expect(File::get("$this->directory/cpresources/$hash/plugin.js"))->toBe('window.plugin = true;')
        ->and(File::directories("$this->directory/cpresources"))->toHaveCount(1);
});

it('does not write to the resource directory when runtime publishing is disabled', function() {
    Cms::config()->resourceBasePath = "$this->directory/cpresources";

    $assetManager = runtimeAssetManager();
    $bundle = $assetManager->getBundle(PublishAssetBundlesCommandTestAsset::class);

    expect($assetManager->getAssetUrl($bundle, 'plugin.js'))->toContain('/plugin.js')
        ->and(File::exists("$this->directory/cpresources"))->toBeFalse();
});
