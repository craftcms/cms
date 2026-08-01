<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\View\LegacyAssets;

use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\LegacyAssets\LegacyAssetInterface;
use CraftCms\Yii2Adapter\Http\RegisterLegacyCompatAssets;

use function CraftCms\Cms\craftAsset;

/**
 * Backwards-compatibility shims for deprecated legacy CP jQuery plugins that
 * used to live in the craftcms-legacy CP bundle.
 *
 * Core no longer uses these plugins; the shims exist so third-party code that
 * still calls them keeps working, while logging a deprecation warning. The
 * shims live in legacy/web/assets/cpcompat/cp-compat.js (published to
 * public/vendor/craft/adapter/cpcompat). Loaded CP-wide via {@see RegisterLegacyCompatAssets}.
 *
 * component-select-input.js is a related but distinct case: the real (not
 * stubbed) legacy `Craft.ComponentSelectInput` implementation, relocated here
 * so the core bundle can drop it while the `componentSelect.twig` `jsClass`
 * escape hatch keeps working for plugin subclasses. It defines its class at
 * top-level eval, so it is registered ahead of cp-compat.js.
 *
 * @internal
 */
class CpCompatAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('adapter/cpcompat/component-select-input.js'));
        $htmlStack->jsFile(craftAsset('adapter/cpcompat/legacy-settings-island.js'));
        $htmlStack->jsFile(craftAsset('adapter/cpcompat/cp-compat.js'));
    }
}
