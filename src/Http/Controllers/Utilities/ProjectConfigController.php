<?php

declare(strict_types=1);

namespace CraftCms\Cms\Http\Controllers\Utilities;

use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Utility\Utilities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;

use function CraftCms\Cms\t;

final readonly class ProjectConfigController
{
    use RespondsWithFlash;

    public function __construct(Utilities $utilities)
    {
        abort_unless($utilities->checkAuthorization(Utilities\ProjectConfig::class), 403, 'User is not authorized to perform this action.');
    }

    public function diff(Request $request): string
    {
        return ProjectConfigHelper::diff($request->boolean('invert'));
    }

    public function rebuild(ProjectConfig $projectConfig): Response
    {
        abort_if($projectConfig->readOnly, 403, 'Rebuilding the project config is not allowed while it’s in read-only mode.');

        $projectConfig->rebuild();

        return $this->asSuccess(t('Project config rebuilt successfully.'));
    }

    public function discard(ProjectConfig $projectConfig): Response
    {
        abort_if($projectConfig->readOnly, 403, 'Rebuilding the project config is not allowed while it’s in read-only mode.');

        $projectConfig->regenerateExternalConfig();

        return $this->asSuccess(t('External project config changes discarded.'));
    }

    public function download(ProjectConfig $projectConfig): Response
    {
        $config = $projectConfig->get();
        $splitConfig = ProjectConfigHelper::splitConfigIntoComponents($config);

        $zip = new ZipArchive;
        $filename = Str::uuid()->toString().'.zip';
        $zipPath = Storage::disk('craft-tmp')->path($filename);

        throw_if($zip->open($zipPath, ZipArchive::CREATE) !== true, RuntimeException::class, 'Cannot create zip at '.$zipPath);

        foreach ($splitConfig as $path => $pathConfig) {
            $content = Yaml::dump(ProjectConfigHelper::cleanupConfig($pathConfig), 20, 2);
            $zip->addFromString($path, $content);
        }

        $zip->close();

        $contents = Storage::disk('craft-tmp')->get($filename);

        Storage::disk('craft-tmp')->delete($filename);

        return response()->streamDownload(function () use ($contents) {
            echo $contents;
        }, 'project.zip', [
            'content-type' => 'application/zip',
        ]);
    }
}
