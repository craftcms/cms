<?php

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\prismjs\PrismJsAsset;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Utility\Utility;
use Symfony\Component\Yaml\Yaml;

/**
 * ProjectConfig represents a ProjectConfig utility.
 */
final class ProjectConfig extends Utility
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function displayName(): string
    {
        return Craft::t('app', 'Project Config');
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function id(): string
    {
        return 'project-config';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function icon(): string
    {
        return 'gear';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public static function contentHtml(): string
    {
        $projectConfig = app(ProjectConfigService::class);
        $areChangesPending = $projectConfig->areChangesPending(force: true);
        $view = Craft::$app->getView();

        if ($areChangesPending) {
            $view->registerAssetBundle(PrismJsAsset::class);
            $view->registerTranslations('app', [
                'Show all changes',
            ]);
            $invert = (
                ! $projectConfig->readOnly &&
                ! $projectConfig->writeYamlAutomatically &&
                $projectConfig->get('dateModified') > $projectConfig->get('dateModified', true)
            );
        } else {
            $invert = false;
        }

        return $view->renderTemplate('_components/utilities/ProjectConfig.twig', [
            'readOnly' => $projectConfig->readOnly,
            'invert' => $invert,
            'yamlExists' => $projectConfig->writeYamlAutomatically || $projectConfig->getDoesExternalConfigExist(),
            'areChangesPending' => $areChangesPending,
            'entireConfig' => Yaml::dump($projectConfig->get(), 20, 2),
        ]);
    }
}
