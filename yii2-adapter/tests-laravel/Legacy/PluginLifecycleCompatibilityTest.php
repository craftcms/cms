<?php

declare(strict_types=1);

use craft\base\Plugin;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Yii2Adapter\Yii2ServiceProvider;

beforeEach(function() {
    $this->app->register(Yii2ServiceProvider::class);
});

it('creates and runs adapter plugins through the shared plugin interface', function() {
    $plugins = app(Plugins::class);

    new ReflectionProperty($plugins, 'composerPluginInfo')->setValue($plugins, [
        'legacy' => [
            'class' => AdapterLifecycleTestPlugin::class,
            'handle' => 'legacy',
            'name' => 'Legacy',
            'packageName' => 'craftcms/legacy',
            'version' => '1.0.0',
            'basePath' => __DIR__,
        ],
    ]);

    $plugin = $plugins->createPlugin('legacy');

    expect($plugin)
        ->toBeInstanceOf(PluginInterface::class)
        ->toBeInstanceOf(AdapterLifecycleTestPlugin::class);

    expect(function() use ($plugin, $plugins): void {
        $plugin->bootPlugin($plugins);
        $plugin->publishAssets();
        $plugin->removeAssets();
    })->not()->toThrow(Throwable::class);
});

class AdapterLifecycleTestPlugin extends Plugin
{
}
