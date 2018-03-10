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
    public function init()
    {
        $this->sourcePath = '@lib/element-resize-detector';

        $this->js = [
            'element-resize-detector.js',
        ];

        parent::init();
    }
}
