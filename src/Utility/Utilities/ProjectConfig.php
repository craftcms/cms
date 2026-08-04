<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Utility\Utility;
use Override;
use Symfony\Component\Yaml\Yaml;

use function CraftCms\Cms\t;

/**
 * ProjectConfig represents a ProjectConfig utility.
 */
class ProjectConfig extends Utility
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
            $invert = (
                ! $projectConfig->readOnly &&
                ! $projectConfig->writeYamlAutomatically &&
                $projectConfig->get('dateModified') > $projectConfig->get('dateModified', true)
            );
        } else {
            $invert = false;
        }

        return Html::tag('ProjectConfig', attributes: [
            ':read-only' => $projectConfig->readOnly,
            ':invert' => $invert,
            ':yaml-exists' => ($projectConfig->writeYamlAutomatically || $projectConfig->getDoesExternalConfigExist()) ? 'true' : 'false',
            ':are-changes-pending' => $areChangesPending,
            'entire-config' => Yaml::dump($projectConfig->get(), 20, 2),
        ]);
    }
}
