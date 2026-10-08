<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Support\Facades\Deprecator;
use CraftCms\Cms\View\HtmlStack;

/**
 * Registers nothing: Craft 6 doesn't ship htmx.
 *
 * It exists so a bundle that merely names this one in its `depends` resolves
 * instead of throwing. A screen genuinely driving its UI through htmx needs to
 * bundle its own copy, which is what the deprecation says.
 *
 * @deprecated
 *
 * @internal
 *
 * @since 6.0.0
 */
class HtmxAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        Deprecator::log('HtmxAsset', self::class.' is deprecated. Craft 6 no longer bundles htmx, so nothing is registered — a bundle relying on htmx should ship its own copy.');
    }
}
