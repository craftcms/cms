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
class GeneralSettingsAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        // $htmlStack->jsFile(craftAsset('legacy/generalsettings/dist/rebrand.js'));
        // $htmlStack->cssFile(craftAsset('legacy/generalsettings/dist/css/rebrand.css'));
    }
}
