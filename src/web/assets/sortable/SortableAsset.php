<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\sortable;

use yii\web\AssetBundle;

/**
 * Sortable asset bundle.
 */
class SortableAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init(): void
    {
        $this->sourcePath = '@lib/sortable';

        $this->css = [];

        $this->js = [
            'Sortable.js',
        ];

        parent::init();
    }
}
