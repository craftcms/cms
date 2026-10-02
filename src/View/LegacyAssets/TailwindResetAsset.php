<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 *
 * @since 6.0.0
 */
class TailwindResetAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->cssFile(craftAsset('legacy/tailwindreset/dist/css/tailwind_reset.css'));
        $htmlStack->cssFile(craftAsset('legacy/tailwindreset/dist/tailwind_reset.js'));
    }
}
