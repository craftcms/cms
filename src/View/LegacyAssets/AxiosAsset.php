<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * The global `axios` for legacy JavaScript. Core doesn't load it itself; it's
 * registered CP-wide by yii2-adapter for plugin code, and by the Plugin Store,
 * which still uses it.
 *
 * @deprecated
 *
 * @internal
 *
 * @since 6.0.0
 */
class AxiosAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/axios/dist/axios.js'));
    }
}
