<?php

declare(strict_types=1);

use CraftCms\Cms\Console\Commands\Setup\PublishCommand;
use CraftCms\Cms\Plugin\Plugins;
use CraftCms\Cms\Support\File;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

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
