<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig\Commands;

use craft\helpers\FileHelper;
use CraftCms\Cms\Console\CraftCommand;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\ProjectConfig\ProjectConfigHelper;
use CraftCms\Cms\Support\Facades\I18N;
use Illuminate\Console\Command;
use Symfony\Component\Yaml\Yaml;

use function Laravel\Prompts\confirm;

/**
 * Exports the entire project config to a single file.
 *
 * @param  string|null  $path  The path the project config should be exported to.
 *                             Can be any of the following:
 *
 * - A full file path
 * - A folder path (export will be saved in there with a dynamically-generated name)
 * - A filename (export will be saved in the working directory with the given name)
 * - Blank (export will be saved in the working directly with a dynamically-generated name)
 */
final class ExportCommand extends Command
{
    use CraftCommand;

    protected $signature = 'craft:project-config:export
        {path?}
        {--external : Whether to pull values from the project config YAML files instead of the loaded config.}
        {--overwrite : Whether to overwrite an existing export file, if a specific file path is given.}
    ';

    protected $description = 'Exports the entire project config to a single file.';

    protected $aliases = ['project-config/export', 'pc:export', 'pc/export'];

    public function handle(ProjectConfig $projectConfig): int
    {
        $path = $this->argument('path');

        if ($path !== null) {
            // Prefix with the working directory if a relative path or no path is given
            if (str_starts_with($path, '.') || ! str_contains(FileHelper::normalizePath($path, '/'), '/')) {
                $path = getcwd().DIRECTORY_SEPARATOR.$path;
            }

            $path = FileHelper::normalizePath($path);
        } else {
            $path = getcwd();
        }

        if (is_dir($path)) {
            $i = 0;
            do {
                $testPath = $path.DIRECTORY_SEPARATOR.'project-config-'.($this->option('external') ? 'external' : 'internal').'--'.date('Y-m-d').($i ? "--$i" : '').'.yaml';
                $i++;
            } while (file_exists($testPath));
            $path = $testPath;
        }

        if (is_file($path)) {
            if (! $this->option('overwrite')) {
                if (! $this->input->isInteractive()) {
                    $this->components->error("$path already exists. Retry with the --overwrite flag to overwrite it.");

                    return self::FAILURE;
                }

                if (! confirm("$path already exists. Overwrite?")) {
                    $this->components->warn('Aborted.');

                    return self::SUCCESS;
                }
            }

            unlink($path);
        }

        $this->components->task(
            'Exporting the '.($this->option('external') ? 'external' : 'loaded').' project config data ... ',
            function () use ($projectConfig, $path) {
                $config = $projectConfig->get(null, $this->option('external'));
                $content = Yaml::dump(ProjectConfigHelper::cleanupConfig($config), 20, 2);

                FileHelper::writeToFile($path, $content);
            }
        );

        $size = I18N::getFormatter()->asShortSize(filesize($path));

        $this->components->info("Exported to <fg=cyan>{$path}</> ($size)");

        return self::SUCCESS;
    }
}
