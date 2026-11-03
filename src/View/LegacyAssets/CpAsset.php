<?php

declare(strict_types=1);

namespace CraftCms\Cms\View\LegacyAssets;

use CraftCms\Cms\Cp\Cp;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Cms\View\HtmlStack;

use function CraftCms\Cms\craftAuth;

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

        if (! craftAuth()->check()) {
            $except[] = 'runQueueAutomatically';
        }

        return Cp::config()->except($except)->all();
    }
}
