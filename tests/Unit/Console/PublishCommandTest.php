<?php

declare(strict_types=1);

use CraftCms\Cms\Console\Commands\Setup\PublishCommand;
use CraftCms\Cms\Support\File;

it('removes stale Craft public assets before publishing', function () {
    $publicPath = storage_path('framework/testing/setup-publish-public');
    $originalPublicPath = public_path();

    File::deleteDirectory($publicPath);
    File::ensureDirectoryExists("{$publicPath}/vendor/craft/build");
    File::put("{$publicPath}/vendor/craft/old-root.js", 'stale');
    File::put("{$publicPath}/vendor/craft/build/old-build.js", 'stale');

    app()->usePublicPath($publicPath);

    try {
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

        $command->handle();

        expect(File::exists("{$publicPath}/vendor/craft/old-root.js"))->toBeFalse()
            ->and(File::exists("{$publicPath}/vendor/craft/build/old-build.js"))->toBeFalse()
            ->and(File::exists("{$publicPath}/vendor/craft/build/fresh-build.js"))->toBeTrue()
            ->and($command->calls)->toBe([
                ['vendor:publish', ['--tag' => 'craftcms-assets', '--force' => true]],
                ['vendor:publish', ['--tag' => 'craftcms-config']],
                ['vendor:publish', ['--tag' => 'craftcms-console']],
            ]);
    } finally {
        app()->usePublicPath($originalPublicPath);
        File::deleteDirectory($publicPath);
    }
});
