<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\highlight;

use craft\web\AssetBundle;

/**
 * TanStack Highlight asset bundle.
 *
 * The control panel always registers `Craft.highlighter` now, which can be used to syntax-highlight code:
 *
 * ```js
 * const html = Craft.highlighter.highlight(code, {lang: 'json'}).html;
 * ```
 *
 * @since 5.12.0
 * @deprecated 6.0.0 `Craft.highlighter` is always available in the control panel.
 */
class HighlightAsset extends AssetBundle
{
}
