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
class PluginStoreAsset implements LegacyAssetInterface
{
    public array $depends = [
        // @TODO Remove once the Plugin Store no longer uses axios. Until then,
        // this is the only core screen that loads it without yii2-adapter.
        AxiosAsset::class,
        CpAsset::class,
        VueAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/pluginstore/dist/js/app.js'));
        $htmlStack->cssFile(craftAsset('legacy/pluginstore/dist/css/app.css'));
    }
}
