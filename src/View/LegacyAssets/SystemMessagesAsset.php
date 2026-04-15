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
class SystemMessagesAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/systemmessages/dist/system_messages.js'));
        $htmlStack->cssFile(craftAsset('legacy/systemmessages/dist/css/system_messages.css'));
    }
}
