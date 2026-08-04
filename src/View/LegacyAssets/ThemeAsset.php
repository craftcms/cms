<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Cms;
use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class ThemeAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        if (request()->isCpRequest()) {
            $htmlStack->cssFile(craftAsset('legacy/theme/dist/cp.css'));
        } else {
            $htmlStack->cssFile(craftAsset('legacy/theme/dist/fe.css'));
            if (Cms::config()->systemTemplateCss) {
                $htmlStack->cssFile(Cms::config()->systemTemplateCss);
            }
        }
    }
}
