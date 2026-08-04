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
class FocalPointAsset implements LegacyAssetInterface
{
    public array $depends = [
        JqueryAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/focalpoint/dist/FocalPoint.js'));
        $htmlStack->cssFile(craftAsset('legacy/focalpoint/dist/css/FocalPoint.css'));
    }
}
