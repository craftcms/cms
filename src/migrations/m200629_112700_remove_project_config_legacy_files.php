<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;
use craft\helpers\Path as PathHelper;
use craft\helpers\StringHelper;
use craft\services\ProjectConfig as ProjectConfigService;
use Symfony\Component\Yaml\Yaml;

/**
 * m200629_112700_remove_project_config_legacy_files migration.
 */
class m200629_112700_remove_project_config_legacy_files extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.5.7', '<')) {
            $pathService = Craft::$app->getPath();
            $baseFile = $pathService->getConfigPath() . DIRECTORY_SEPARATOR . ProjectConfigService::CONFIG_FILENAME;
            $configData = [];
            $previousFiles = [];

            $traverseFile = function($filePath) use (&$traverseFile, &$configData, &$previousFiles, $pathService) {
                $fileConfig = Yaml::parse(file_get_contents($filePath));
                $fileDir = pathinfo($filePath, PATHINFO_DIRNAME);
                $previousFiles[] = $filePath;

                if (isset($fileConfig['imports'])) {
                    foreach ($fileConfig['imports'] as $file) {
                        if (!StringHelper::startsWith($file, '/')) {
                            $file = $pathService->getConfigPath() . DIRECTORY_SEPARATOR . $file;
                        } else {
                            $file = $fileDir . DIRECTORY_SEPARATOR . $file;
                        }

                        if (PathHelper::ensurePathIsContained($file) && file_exists($file)) {
                            $traverseFile($file);
                        }
                    }
                }

                unset($fileConfig['imports']);
                $configData = array_merge($configData, $fileConfig);
            };

            if (file_exists($baseFile)) {
                echo '    > Loading legacy configuration file structure ... ';
                $traverseFile($baseFile);
                echo "done\n";

                $backupFile = pathinfo(ProjectConfigService::CONFIG_FILENAME, PATHINFO_FILENAME) . date('-Y-m-d-His') . '.yaml';
                echo "    > Backing up the existing project config to $backupFile and moving to config backup folder ... ";
                $backupPath = $pathService->getConfigBackupPath() . DIRECTORY_SEPARATOR . $backupFile;
                FileHelper::writeToFile($backupPath, Yaml::dump($configData, 20, 2));
                echo "done\n";
            }

            echo "    > Removing legacy previous project config files\n";

            foreach ($previousFiles as $filePath) {
                echo "      > Removing {$filePath}\n";
                unlink($filePath);
            }

            echo "    > Legacy project config files removed\n";
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200629_112700_remove_project_config_legacy_files cannot be reverted.\n";
        return false;
    }
}
