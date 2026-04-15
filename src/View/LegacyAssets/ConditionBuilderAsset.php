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
class ConditionBuilderAsset implements LegacyAssetInterface
{
    public array $depends = [
        HtmxAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/conditionbuilder/dist/ConditionBuilder.js'));
    }
}
