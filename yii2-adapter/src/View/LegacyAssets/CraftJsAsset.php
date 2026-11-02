<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\CpAsset;
use CraftCms\Cms\View\LegacyAssets\DatepickerI18nAsset;
use CraftCms\Cms\View\LegacyAssets\IframeResizerAsset;
use CraftCms\Cms\View\LegacyAssets\JqueryPaymentAsset;
use CraftCms\Cms\View\LegacyAssets\JqueryUiAsset;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;
use CraftCms\Cms\View\LegacyAssets\PicturefillAsset;
use CraftCms\Cms\View\LegacyAssets\SelectizeAsset;

use function CraftCms\Cms\craftAsset;

/**
 * The legacy `Craft` helpers and jQuery plugins (`Craft.js`), which plugins
 * got from `CpAsset` on any page they registered it on, front end included,
 * along with the vendor libraries `CpAsset` used to bring.
 *
 * @internal
 */
class CraftJsAsset implements LegacyAssetInterface
{
    public array $depends = [
        CpAsset::class,
        JqueryUiAsset::class,
        JqueryPaymentAsset::class,
        DatepickerI18nAsset::class,
        PicturefillAsset::class,
        SelectizeAsset::class,
        IframeResizerAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/cpcompat/dist/craft.js'));
    }
}
