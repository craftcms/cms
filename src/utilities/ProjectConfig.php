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
        return Craft::getAlias('@appicons/sliders.svg');
    }

    /**
     * @inheritdoc
     */
    public static function contentHtml(): string
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $areChangesPending = $projectConfig->areChangesPending();
        $view = Craft::$app->getView();

        if ($areChangesPending) {
            $view->registerAssetBundle(PrismJsAsset::class);
            $view->registerTranslations('app', [
                'Show all changes',
            ]);
            $invert = (
                !$projectConfig->readOnly &&
                !$projectConfig->writeYamlAutomatically &&
                $projectConfig->get('dateModified') > $projectConfig->get('dateModified', true)
            );
        } else {
            $invert = false;
        }

        return $view->renderTemplate('_components/utilities/ProjectConfig', [
            'readOnly' => $projectConfig->readOnly,
            'invert' => $invert,
            'yamlExists' => $projectConfig->writeYamlAutomatically || $projectConfig->getDoesYamlExist(),
            'areChangesPending' => $areChangesPending,
            'entireConfig' => Yaml::dump($projectConfig->get(), 20, 2),
        ]);
    }
}
