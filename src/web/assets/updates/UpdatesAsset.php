<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.com/license
 */

namespace craft\web\assets\updates;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\View;

/**
 * Asset bundle for the Updates utility
 */
class UpdatesAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = __DIR__.'/dist';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'updates.css',
        ];

        $this->js = [
            'UpdatesUtility'.$this->dotJs(),
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
                'You’re all up-to-date!',
                'Critical',
                'Update',
                'Update to {version}',
                'Update all',
                'Craft’s <a href="http://craftcms.com/license" target="_blank">Terms and Conditions</a> have changed.',
                'I agree.',
                'Seriously, update.',
                'Show all',
            ]);
        }
    }
}
