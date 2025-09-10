<?php

namespace CraftCms\Cms\Http\Controllers\Utilities;

use Craft;
use craft\helpers\FileHelper;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;

/**
 * @since 6.0.0
 */
final class ProjectConfigController
{
    use RespondsWithFlash;

    public function __construct(Utilities $utilities)
    {
        if (! $utilities->checkAuthorization(Utilities\ProjectConfig::class)) {
            abort(403, 'User is not authorized to perform this action.');
        }
    }

    public function diff(Request $request): string
    {
        return ProjectConfigHelper::diff($request->boolean('invert'));
    }

    public function rebuild(ProjectConfig $projectConfig): Response
    {
        abort_if($projectConfig->readOnly, 403, 'Rebuilding the project config is not allowed while it’s in read-only mode.');

        $projectConfig->rebuild();

        return $this->asSuccess(Craft::t('app', 'Project config rebuilt successfully.'));
    }

    public function discard(ProjectConfig $projectConfig): Response
    {
        abort_if($projectConfig->readOnly, 403, 'Rebuilding the project config is not allowed while it’s in read-only mode.');

        $projectConfig->regenerateExternalConfig();

        return $this->asSuccess(Craft::t('app', 'External project config changes discarded.'));
    }

    public function download(ProjectConfig $projectConfig): Response
    {
        $config = $projectConfig->get();
        $splitConfig = ProjectConfigHelper::splitConfigIntoComponents($config);

        $zip = new ZipArchive;
        $zipPath = Craft::$app->getPath()->getTempPath().'/'.Str::uuid()->toString().'.zip';

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot create zip at '.$zipPath);
        }

        foreach ($splitConfig as $path => $pathConfig) {
            $content = Yaml::dump(ProjectConfigHelper::cleanupConfig($pathConfig), 20, 2);
            $zip->addFromString($path, $content);
        }

        $zip->close();

        app()->terminating(fn () => FileHelper::unlink($zipPath));

        return response()->download($zipPath, 'project.zip');
    }
}
