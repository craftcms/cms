<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\theme;

use Craft;
use craft\web\AssetBundle;
use CraftCms\Cms\Config\GeneralConfig;

/**
 * Asset bundle for the control panel
 */
class ThemeAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = __DIR__ . '/dist';

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if (Craft::$app->getRequest()->getIsCpRequest()) {
            $this->css = ['cp.css'];
        } else {
            $this->css = ['fe.css'];
            $generalConfig = app(GeneralConfig::class);
            if ($generalConfig->systemTemplateCss) {
                $this->css[] = $generalConfig->systemTemplateCss;
            }
        }
    }
}
