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
class PluginStoreAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
        VueAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/pluginstore/dist/js/app.js'));
        $htmlStack->cssFile(craftAsset('legacy/pluginstore/dist/css/app.css'));
    }
}
