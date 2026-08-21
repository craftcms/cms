<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Support;

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Path as CorePath;

use function Illuminate\Filesystem\join_paths;

class Path extends CorePath
{
    public function vendor(string $path = ''): string
    {
        $vendorPath = Aliases::get('@vendor', false);

        if ($vendorPath === false) {
            return parent::vendor($path);
        }

        return File::normalizePath($path === '' ? $vendorPath : join_paths($vendorPath, $path));
    }

    public function siteTemplates(string $path = ''): string
    {
        $templatesPath = Aliases::get('@templates', false);

        if ($templatesPath === false) {
            return parent::siteTemplates($path);
        }

        return File::normalizePath($path === '' ? $templatesPath : join_paths($templatesPath, $path));
    }
}
