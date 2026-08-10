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
 * shims live in the craftcms-legacy cpcompat bundle. Loaded CP-wide via
 * {@see RegisterLegacyCompatAssets}.
 *
 * component-select-input.js is a related but distinct case: the real (not
 * stubbed) legacy `Craft.ComponentSelectInput` implementation, relocated into
 * that bundle so the core CP bundle can drop it while the `componentSelect.twig`
 * `jsClass` escape hatch keeps working for plugin subclasses. It defines its
 * class at top-level eval, so it is registered ahead of cp-compat.js.
 *
 * @internal
 */
class CpCompatAsset implements LegacyAssetInterface
{
    public array $depends = [];

    public function register(HtmlStack $htmlStack): void
    {
        $htmlStack->jsFile(craftAsset('legacy/cpcompat/dist/component-select-input.js'));
        $htmlStack->jsFile(craftAsset('legacy/cpcompat/dist/legacy-html-control.js'));
        $htmlStack->jsFile(craftAsset('legacy/cpcompat/dist/cp-compat.js'));
    }
}
