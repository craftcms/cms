<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\htmx;

use craft\web\AssetBundle;
use CraftCms\Cms\Support\Facades\Deprecator;

/**
 * Htmx asset bundle.
 *
 * Registers nothing: Craft 6 doesn't ship htmx. In Craft 5 only core pulled this
 * in — `ConditionBuilderAsset` and `CpModalResponseFormatter` — and both of those
 * were rebuilt on Vue and web components, so there's no v6 asset to delegate to
 * the way the other bundles in this directory do.
 *
 * It exists because a plugin that merely *names* this bundle in its `depends`
 * shouldn't 500. That's the common case, and the one worth fixing: Shopify's
 * `ShopifyCpAsset` lists it while using no `hx-*` attributes anywhere, so simply
 * resolving the class is enough to make its product edit screen render.
 *
 * A plugin genuinely driving UI through htmx is the case this *can't* fix, which
 * is what the deprecation is for — inert `hx-*` attributes with a warning beat a
 * stack trace, and beat silence. Such a plugin needs to bundle htmx itself.
 *
 * @deprecated 6.0.0
 */
class HtmxAsset extends AssetBundle
{
    public function registerAssetFiles($view): void
    {
        Deprecator::log('HtmxAsset', 'craft\\web\\assets\\htmx\\HtmxAsset is deprecated. Craft 6 no longer bundles htmx, so nothing is registered — a plugin relying on htmx should bundle its own copy.');
    }
}
