<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\DashboardAsset;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;

use function CraftCms\Cms\craftAsset;

class DashboardCompatAsset implements LegacyAssetInterface
{
    public array $depends = [DashboardAsset::class];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/cpcompat/dist/dashboard.js'));
    }
}
