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
class DbBackupAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/dbbackup/dist/DbBackupUtility.js'));
    }
}
