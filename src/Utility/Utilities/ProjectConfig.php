<?php

declare(strict_types=1);

namespace CraftCms\Cms\Utility\Utilities;

use CraftCms\Cms\Cp\VueComponent;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigService;
use CraftCms\Cms\Utility\Utility;
use Symfony\Component\Yaml\Yaml;

use function CraftCms\Cms\t;

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
        return t('Project Config');
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

        if ($areChangesPending) {
            $invert = (
                ! $projectConfig->readOnly &&
                ! $projectConfig->writeYamlAutomatically &&
                $projectConfig->get('dateModified') > $projectConfig->get('dateModified', true)
            );
        } else {
            $invert = false;
        }

        return VueComponent::render('ProjectConfig', [
            ':read-only' => $projectConfig->readOnly ? 'true' : 'false',
            ':invert' => $invert ? 'true' : 'false',
            ':yaml-exists' => ($projectConfig->writeYamlAutomatically || $projectConfig->getDoesExternalConfigExist()) ? 'true' : 'false',
            ':are-changes-pending' => $areChangesPending ? 'true' : 'false',
            'entire-config' => Yaml::dump($projectConfig->get(), 20, 2),
        ]);
    }
}
