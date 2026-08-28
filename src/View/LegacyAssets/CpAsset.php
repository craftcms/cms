<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;
use Illuminate\Support\Facades\Auth;

use function CraftCms\Cms\craftAsset;

/**
 * @deprecated
 *
 * @internal
 */
class CpAsset implements LegacyAssetInterface
{
    public array $depends = [
        // TailwindResetAsset::class,
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
        // The design tokens and `cp:` utilities. The legacy Twig shell renders
        // its own document rather than the Inertia one, so nothing else brings
        // these in — without them the compatibility aliases in the stylesheet
        // below (`--xs: var(--c-spacing-sm)`) have nothing to resolve against,
        // and no `cp:` class does anything on a legacy page.
        $htmlStack->html(Cp::viteUtilities()->toHtml(), Position::Head);

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
        $craftJson = Json::encode($this->craftData());
        $js = <<<JS
        window.Craft = Object.assign(window.Craft || {}, $craftJson)
        JS;
        $htmlStack->js($js, Position::Head);
    }

    /**
     * The legacy `window.Craft` config.
     *
     * The data now lives on {@see Cp::config()} (shared with the Inertia CP).
     * That method is a superset, so we drop the keys it adds for the Inertia
     * side that were never part of `window.Craft`. `cpTrigger` and
     * `runQueueAutomatically` are also force-included by `Cp::config()` for
     * Inertia, so they're stripped here unless the legacy conditions
     * (CP request / authenticated user) actually apply.
     */
    /** @return array<string, mixed> */
    private function craftData(): array
    {
        $except = [
            'cpLogoUrl',
            'cpUrl',
            'defaultCpLocale',
            'rememberedUserSessionDuration',
        ];

        if (! request()->isCpRequest()) {
            $except[] = 'cpTrigger';
        }

        if (! Auth::check()) {
            $except[] = 'runQueueAutomatically';
        }

        return Cp::config()->except($except)->all();
    }
}
