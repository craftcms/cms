<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

declare(strict_types=1);

namespace craft\web\assets\xregexp;

use craft\web\AssetBundle;

/**
 * XRegExp asset bundle.
 *
 * @deprecated 6.0.0
 */
class XregexpAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';
        $this->js = [
            'xregexp-all.js',
        ];

        parent::init();
    }
}
