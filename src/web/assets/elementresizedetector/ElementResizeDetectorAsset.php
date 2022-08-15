<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\elementresizedetector;

use craft\web\AssetBundle;

/**
 * ElementResizeDetector asset bundle.
 */
class ElementResizeDetectorAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->js = [
            'element-resize-detector.js',
        ];

        parent::init();
    }
}
