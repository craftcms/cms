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
class GarnishAsset implements LegacyAssetInterface
{
    public array $depends = [
        JqueryAsset::class,
        JqueryTouchEventsAsset::class,
        VelocityAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/garnish/dist/garnish.js'));
    }
}
