<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class AdminTableAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
        VueAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/admintable/dist/js/app.js'));
        $htmlStack->cssFile(craftAsset('legacy/admintable/dist/css/app.css'));
    }
}
