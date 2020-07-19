<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\web\assets\prismjs\PrismJsAsset;
use Symfony\Component\Yaml\Yaml;

/**
 * ProjectConfig represents a ProjectConfig utility.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.0
 */
class ProjectConfig extends Utility
{
    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('app', 'Project Config');
    }

    /**
     * @inheritdoc
     */
    public static function id(): string
    {
        return 'project-config';
    }

    /**
     * @inheritdoc
     */
    public static function iconPath()
    {
        return Craft::getAlias('@app/icons/sliders.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $css = <<<CSS
#config-container {
  max-height: 500px;
  overflow: auto;
}
#config-container pre {
  margin: 0;
  padding: 0;
  background-color: transparent;
}
CSS;

        $view = Craft::$app->getView();
        $view->registerAssetBundle(PrismJsAsset::class);
        $view->registerCss($css);

        $projectConfig = Craft::$app->getProjectConfig();
        return $view->renderTemplate('_components/utilities/ProjectConfig', [
            'changesPending' => $projectConfig->areChangesPending(null, true),
            'entireConfig' => Yaml::dump($projectConfig->get(), 20, 2),
        ]);
    }
}
