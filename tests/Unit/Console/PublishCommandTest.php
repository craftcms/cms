<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Console\Commands\Setup\PublishCommand;
use CraftCms\Cms\Plugin\Contracts\PluginInterface;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Tests\TestClasses\TestPlugin\src\TestPlugin;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Yaml\Yaml;

it('removes stale Craft public assets before publishing', function () {
    $publicPath = storage_path('framework/testing/setup-publish-public');
    $originalPublicPath = public_path();

    File::deleteDirectory($publicPath);
    File::ensureDirectoryExists("{$publicPath}/vendor/craft/build");
    File::put("{$publicPath}/vendor/craft/old-root.js", 'stale');
    File::put("{$publicPath}/vendor/craft/build/old-build.js", 'stale');

    app()->usePublicPath($publicPath);

    try {
        $plugins = Mockery::mock(Plugins::class);
        $plugins->shouldReceive('publishPluginAssets')->once();

        $command = new class extends PublishCommand
        {
            public array $calls = [];

            public function call($command, array $arguments = []): int
            {
                $this->calls[] = [$command, $arguments];

                if (($arguments['--tag'] ?? null) === 'craftcms-assets') {
                    File::ensureDirectoryExists(public_path('vendor/craft/build'));
                    File::put(public_path('vendor/craft/build/fresh-build.js'), 'fresh');
                }

                return 0;
            }
        };

        $command->handle($plugins);

        expect(File::exists("{$publicPath}/vendor/craft/old-root.js"))->toBeFalse()
            ->and(File::exists("{$publicPath}/vendor/craft/build/old-build.js"))->toBeFalse()
            ->and(File::exists("{$publicPath}/vendor/craft/build/fresh-build.js"))->toBeTrue()
            ->and($command->calls)
            ->toContain(['vendor:publish', ['--tag' => 'craftcms-assets', '--force' => true]])
            ->toContain(['vendor:publish', ['--tag' => 'craftcms-config']])
            ->toContain(['vendor:publish', ['--tag' => 'craftcms-console']]);
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($publicPath);
    }
});

it('leaves a symlinked public asset directory and its target alone', function () {
    $publicPath = storage_path('framework/testing/setup-publish-public');
    $targetPath = storage_path('framework/testing/setup-publish-target');
    $originalPublicPath = public_path();

    File::deleteDirectory($publicPath);
    File::deleteDirectory($targetPath);
    File::ensureDirectoryExists("{$publicPath}/vendor");
    File::ensureDirectoryExists("{$targetPath}/icons/custom-icons");
    File::put("{$targetPath}/icons/custom-icons/craft-cms.svg", '<svg/>');
    symlink($targetPath, "{$publicPath}/vendor/craft");

    app()->usePublicPath($publicPath);

    try {
        $command = new class extends PublishCommand
        {
            public array $calls = [];

            public function call($command, array $arguments = []): int
            {
                $this->calls[] = [$command, $arguments];

                return 0;
            }
        };

        $command->setOutput(
            new OutputStyle(
                new ArrayInput([]),
                new BufferedOutput,
            ),
        );

        $command->handle(app(Plugins::class));

        expect(is_link("{$publicPath}/vendor/craft"))->toBeTrue()
            ->and(File::exists("{$targetPath}/icons/custom-icons/craft-cms.svg"))->toBeTrue()
            ->and($command->calls)->toBe([
                ['vendor:publish', ['--tag' => 'craftcms-config']],
                ['vendor:publish', ['--tag' => 'craftcms-console']],
            ]);
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($publicPath);
        File::deleteDirectory($targetPath);
    }
});

describe('without a database', function () {
    beforeEach(function () {
        $this->directory = storage_path('framework/testing/setup-publish-'.uniqid());
        $this->originalPublicPath = public_path();

        File::writeToFile("{$this->directory}/source/test.js", 'window.testPlugin = true;');
        $folderName = app(ProjectConfig::class)->folderName;
        Path::shouldReceive('config')->with($folderName)->andReturn("{$this->directory}/project");
        TestPlugin::$customScripts = ["{$this->directory}/source/test.js" => 'js/test.js'];
        unset(ServiceProvider::$publishes[TestPlugin::class], ServiceProvider::$publishGroups['test-plugin']);

        config(['database.connections.sqlite.database' => "{$this->directory}/missing.sqlite"]);
        DB::purge('sqlite');
        Context::forgetHidden('craft.isInstalled');
        app()->usePublicPath("{$this->directory}/public");

        app()->instance(Plugins::class, app()->make(Plugins::class, ['composerPlugins' => [
            'craftcms/test-plugin' => [
                'class' => TestPlugin::class,
                'handle' => 'test-plugin',
                'name' => 'Test Plugin',
                'version' => '1.0.1',
                'basePath' => dirname(__DIR__, 2).'/TestClasses/TestPlugin/src',
            ],
        ]]));

        expect(Cms::isInstalled())->toBeFalse();
    });

    afterEach(function () {
        TestPlugin::$customScripts = [];
        unset(ServiceProvider::$publishes[TestPlugin::class], ServiceProvider::$publishGroups['test-plugin']);
        app()->usePublicPath($this->originalPublicPath);
        File::deleteDirectory($this->directory);
    });

    it('publishes assets of plugins enabled in project config files', function (bool $enabled) {
        File::writeToFile("{$this->directory}/project/project.yaml", Yaml::dump([
            'plugins' => ['test-plugin' => ['enabled' => $enabled, 'schemaVersion' => '1.0.0']],
        ]));

        $command = new class extends PublishCommand
        {
            public function call($command, array $arguments = []): int
            {
                return 0;
            }
        };

        $command->handle(app(Plugins::class));

        expect(File::exists("{$this->directory}/public/vendor/craftcms/test-plugin/js/test.js"))->toBe($enabled);
    })->with(['enabled' => true, 'disabled' => false]);

    it('publishes plugins enabled in project config files by their handle tag', function (bool $enabled) {
        File::writeToFile("{$this->directory}/project/project.yaml", Yaml::dump([
            'plugins' => ['test-plugin' => ['enabled' => $enabled, 'schemaVersion' => '1.0.0']],
        ]));

        app(Kernel::class)->rerouteSymfonyCommandEvents();

        $this->artisan('vendor:publish', ['--tag' => 'test-plugin'])->assertSuccessful();

        expect(File::exists("{$this->directory}/public/vendor/craftcms/test-plugin/js/test.js"))->toBe($enabled);
    })->with(['enabled' => true, 'disabled' => false]);

    it('skips plugins that are not native plugins', function () {
        File::writeToFile("{$this->directory}/project/project.yaml", Yaml::dump([
            'plugins' => [
                'legacy-plugin' => ['enabled' => true, 'schemaVersion' => '1.0.0'],
                'test-plugin' => ['enabled' => true, 'schemaVersion' => '1.0.0'],
            ],
        ]));

        app()->instance(Plugins::class, app()->make(Plugins::class, ['composerPlugins' => [
            'acme/legacy-plugin' => [
                'class' => LegacyPlugin::class,
                'handle' => 'legacy-plugin',
            ],
            'craftcms/test-plugin' => [
                'class' => TestPlugin::class,
                'handle' => 'test-plugin',
                'basePath' => dirname(__DIR__, 2).'/TestClasses/TestPlugin/src',
            ],
        ]]));

        app(Plugins::class)->publishPluginAssets();

        expect(File::exists("{$this->directory}/public/vendor/craftcms/test-plugin/js/test.js"))->toBeTrue();
    });
});

abstract class LegacyPlugin implements PluginInterface
{
    public static function create(array $config): PluginInterface
    {
        throw new RuntimeException('Legacy plugins can need a database when they are created.');
    }
}
