<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use Craft;
use craft\web\assets\prismjs\PrismJsAsset;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Support\Facades\AssetRegistry;
use CraftCms\Cms\Utility\Utility;
use Override;
use Symfony\Component\Yaml\Yaml;

use function CraftCms\Cms\t;
use function CraftCms\Cms\template;

/**
 * ProjectConfig represents a ProjectConfig utility.
 */
final class ProjectConfig extends Utility
{
    #[Override]
    public static function displayName(): string
    {
        return t('Project Config');
    }

    #[Override]
    public static function id(): string
    {
        return 'project-config';
    }

    #[Override]
    public static function icon(): string
    {
        return 'gear';
    }

    #[Override]
    public static function contentHtml(): string
    {
        $projectConfig = app(ProjectConfigService::class);
        $areChangesPending = $projectConfig->areChangesPending(force: true);

        if ($areChangesPending) {
            Craft::$app->getView()->registerAssetBundle(PrismJsAsset::class);
            AssetRegistry::translations([
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

        return template('_components/utilities/ProjectConfig', [
            'readOnly' => $projectConfig->readOnly,
            'invert' => $invert,
            'yamlExists' => $projectConfig->writeYamlAutomatically || $projectConfig->getDoesExternalConfigExist(),
            'areChangesPending' => $areChangesPending,
            'entireConfig' => Yaml::dump($projectConfig->get(), 20, 2),
        ]);
    }
}
