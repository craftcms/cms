<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use CraftCms\Cms\Asset\AssetsHelper;

class Io
{
    public function getMaxUploadSize(): float|int
    {
        return AssetsHelper::getMaxUploadSize();
    }

    public function getFileKinds(): array
    {
        return AssetsHelper::getFileKinds();
    }
}
