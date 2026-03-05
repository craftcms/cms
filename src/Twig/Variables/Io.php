<?php

declare(strict_types=1);

namespace CraftCms\Cms\Twig\Variables;

use craft\helpers\Assets;

final class Io
{
    public function getMaxUploadSize(): float|int
    {
        return Assets::getMaxUploadSize();
    }

    public function getFileKinds(): array
    {
        return Assets::getFileKinds();
    }
}
