<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\FileHelper;
use craft\helpers\Path as PathHelper;
use craft\helpers\ProjectConfig;
use craft\services\Sections;
use craft\services\UserGroups;
use Symfony\Component\Yaml\Yaml;

/**
 * m200629_112700_project_config_merge_and_split migration.
 */
class m200629_112700_project_config_merge_and_split extends Migration
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
            echo '    > Creating the new project config component folder ... ';
            $pathService = Craft::$app->getPath();
            $configComponentFolder = $pathService->getProjectConfigComponentsPath();
            
            if (!FileHelper::isWritable($configComponentFolder)) {
                Craft::error('Could not ensure a writable path at ' . $configComponentFolder);
                return false;
            }

            echo "done\n";
                
            echo '    > Loading existing configuration ... ';
            $baseFile = $pathService->getConfigPath() . DIRECTORY_SEPARATOR . $projectConfig->filename;
            $configData = [];
            $previousFiles = [];

            $traverseFile = function($filePath) use (&$traverseFile, &$configData, &$previousFiles) {
                $fileConfig = Yaml::parse(file_get_contents($filePath));
                $fileDir = pathinfo($filePath, PATHINFO_DIRNAME);
                $previousFiles[] = $filePath;

                if (isset($fileConfig['imports'])) {
                    foreach ($fileConfig['imports'] as $file) {
                        if (PathHelper::ensurePathIsContained($file)) {
                            $traverseFile($fileDir . DIRECTORY_SEPARATOR . $file);
                        }
                    }
                }

                unset($fileConfig['imports']);
                $configData = array_merge($configData, $fileConfig);
            };

            $traverseFile($baseFile);
            echo "done\n";

            $backupFile = pathinfo($projectConfig->filename, PATHINFO_FILENAME) . date('-Y-m-d-His') . '.yaml';
            echo "    > Backing up the existing project config to $backupFile and moving to config backup folder ... ";

            $backupPath = $pathService->getConfigBackupPath() . DIRECTORY_SEPARATOR . $backupFile;
            FileHelper::writeToFile($backupPath, Yaml::dump($configData, 20, 2));
            echo "done\n";

            echo "    > Splitting the existing project config into components ... ";
            $splitConfig = ProjectConfig::splitConfigIntoComponents($configData);
            echo "done\n";

            echo "    > Writing components to the individual files ... ";
            foreach ($splitConfig as $filePath => $configData) {
                FileHelper::writeToFile($pathService->getProjectConfigComponentsPath() . DIRECTORY_SEPARATOR . $filePath, Yaml::dump($configData, 20, 2));
            }
            echo "done\n";

            echo "    > Removing previous project config files ... ";

            foreach ($previousFiles as $filePath) {
                $res = unlink($filePath);
            }
            echo "done\n";
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200629_112700_project_config_merge_and_split cannot be reverted.\n";
        return false;
    }
}
