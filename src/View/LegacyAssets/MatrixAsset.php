<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

/**
 * @deprecated
 *
 * @internal
 */
class MatrixAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        // Craft.MatrixInput is provided by the modern module
        // (resources/js/modules/matrix), loaded by both Vite entrypoints; this
        // bundle only remains for its CpAsset dependency.
    }
}
