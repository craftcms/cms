<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\matrixsettings;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for Matrix field settings
 */
class MatrixSettingsAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__ . '/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'MatrixConfigurator' . $this->dotJs(),
        ];

        parent::init();
    }

    /**
     * @inheritdoc
     */
    public function registerAssetFiles($view)
    {
        parent::registerAssetFiles($view);

        if ($view instanceof View) {
            $view->registerTranslations('app', [
                'Are you sure you want to delete this block type?',
                'Are you sure you want to delete this field?',
                'Custom…',
                'Field Type',
                'How you’ll refer to this block type in the templates.',
                'Not translatable',
                'This field is required',
                'Translate for each language',
                'Translate for each site',
                'Translation Key Format',
                'Translation Method',
                'Use this field’s values as search keywords?',
                'What this block type will be called in the CP.',
            ]);
        }
    }
}
