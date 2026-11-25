<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;

/**
 * @deprecated
 *
 * @internal
 *
 * @since 6.0.0
 */
class CpAsset implements LegacyAssetInterface
{
    public array $depends = [
        // TailwindResetAsset::class,
        AnimationBlockerAsset::class,
        D3Asset::class,
        ThemeAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->icons([
            'arrow-down',
            'arrow-left',
            'arrow-right',
            'arrow-up',
            'arrows-rotate',
            'asterisk',
            'asterisk-slash',
            'clipboard',
            'clone',
            'clone-dashed',
            'duplicate',
            'edit',
            'gear',
            'image',
            'image-slash',
            'move',
            'pencil',
            'plus',
            'remove',
            'share',
            'trash',
            'xmark',
            'xmark-large',
        ]);
    }
}
