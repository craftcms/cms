<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use CraftCms\Cms\Asset\AssetsHelper;

/**
 * @since 6.0.0
 */
class Io
{
    public function getMaxUploadSize(): float|int
    {
        return AssetsHelper::getMaxUploadSize();
    }

    /** @return array<string, mixed> */
    public function getFileKinds(): array
    {
        return AssetsHelper::getFileKinds();
    }
}
