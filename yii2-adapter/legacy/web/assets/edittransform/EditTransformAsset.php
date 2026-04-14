<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\edittransform;

use craft\web\InternalAssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * Asset bundle for the Edit Transform page
 */
class EditTransformAsset extends InternalAssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/transforms.css',
        ];

        parent::init();
    }
}
