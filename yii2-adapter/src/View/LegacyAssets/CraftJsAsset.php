<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;

use function CraftCms\Cms\craftAsset;

/**
 * The legacy `Craft` helpers and jQuery plugins (`Craft.js`), which plugins
 * got from `CpAsset` on any page they registered it on, front end included.
 *
 * @internal
 */
class CraftJsAsset implements LegacyAssetInterface
{
    public array $depends = [CpAsset::class];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/cpcompat/dist/craft.js'));
    }
}
