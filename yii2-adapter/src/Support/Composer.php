<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Support;

use CraftCms\Cms\Support\Composer as CoreComposer;
use CraftCms\Cms\Support\Facades\Path;
use CraftCms\Cms\Support\File;
use Override;

use function Illuminate\Filesystem\join_paths;

class Composer extends CoreComposer
{
    #[Override]
    protected function composerCommand(): array
    {
        try {
            return parent::composerCommand();
        } catch (\RuntimeException) {
            $runtimePath = Path::runtime();
            $composerPath = join_paths($runtimePath, 'composer.phar');
            copy(dirname(__DIR__, 2) . '/lib/composer.phar', $composerPath);

            $homePath = join_paths($runtimePath, 'composer');
            File::ensureDirectoryExists($homePath);

            return [$composerPath, $homePath, $composerPath];
        }
    }
}
