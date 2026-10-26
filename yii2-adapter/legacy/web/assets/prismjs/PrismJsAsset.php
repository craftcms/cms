<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\prismjs;

use craft\web\AssetBundle;

/**
 * PrismJs asset bundle.
 * @deprecated 6.0.0 It doesn’t register Prism anymore. Plugins that rely on Prism should bundle it themselves, or use `Craft.highlighter` instead.
 */
class PrismJsAsset extends AssetBundle
{
}
