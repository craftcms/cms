<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\web\assets\theme;

use Craft;
use craft\web\AssetBundle;
use CraftCms\Cms\Cms;

/**
 * Asset bundle for the control panel
 */
class ThemeAsset extends AssetBundle
{
    /**
     * @inheritdoc
     */
    public $sourcePath = '@assetBundles/theme/dist';

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
            $generalConfig = Cms::config();
            if ($generalConfig->systemTemplateCss) {
                $this->css[] = $generalConfig->systemTemplateCss;
            }
        }
    }
}
