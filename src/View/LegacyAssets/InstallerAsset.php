<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class InstallerAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/installer/dist/install.js'));
        $htmlStack->cssFile(craftAsset('legacy/installer/dist/css/install.css'));

        $redirect = Json::encode(Cms::config()->postCpLoginRedirect);
        $htmlStack->js("window.postCpLoginRedirect = $redirect;");
    }
}
