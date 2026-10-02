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
class JqueryUiAsset implements LegacyAssetInterface
{
    public array $depends = [
        JqueryAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/jqueryui/dist/jquery-ui.js'));
    }
}
