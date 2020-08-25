<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\utilities;

use Craft;
use craft\base\Utility;
use craft\helpers\ProjectConfig as ProjectConfigHelper;
use craft\web\assets\prismjs\PrismJsAsset;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;
use SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
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
.pane.highlight {
  max-height: 500px;
  overflow: auto;
}
.pane.highlight pre {
  margin: 0;
  padding: 0;
  background-color: transparent;
}
CSS;

        $view = Craft::$app->getView();
        $view->registerAssetBundle(PrismJsAsset::class);
        $view->registerCss($css);

        return $view->renderTemplate('_components/utilities/ProjectConfig', [
            'diff' => ProjectConfigHelper::diff(),
            'entireConfig' => Yaml::dump(Craft::$app->getProjectConfig()->get(), 20, 2),
        ]);
    }
}
