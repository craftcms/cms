<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class CpAsset implements LegacyAssetInterface
{
    public array $depends = [
        TailwindResetAsset::class,
        AnimationBlockerAsset::class,
        AxiosAsset::class,
        D3Asset::class,
        GarnishAsset::class,
        JqueryAsset::class,
        JqueryTouchEventsAsset::class,
        JqueryUiAsset::class,
        JqueryPaymentAsset::class,
        DatepickerI18nAsset::class,
        SelectizeAsset::class,
        VelocityAsset::class,
        FileUploadAsset::class,
        XregexpAsset::class,
        FabricAsset::class,
        IframeResizerAsset::class,
        ThemeAsset::class,
        PicturefillAsset::class,
    ];

    public function register(HtmlStack $htmlStack): void
    {
        // CP
        $htmlStack->cssFile(craftAsset('legacy/cp/dist/css/cp.css'));
        $htmlStack->jsFile(craftAsset('legacy/cp/dist/cp.js'));

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
        ]);

        // Define the Craft object
        $craftJson = Json::encode(Cp::config());
        $js = <<<JS
        window.Craft = Object.assign(window.Craft || {}, $craftJson)
        JS;
        $htmlStack->js($js, Position::Head);
    }
}
