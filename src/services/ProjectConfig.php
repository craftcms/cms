<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\services;

use Craft;
use craft\helpers\FileHelper;
use craft\helpers\Path as PathHelper;
use Symfony\Component\Yaml\Yaml;
use yii\base\Component;

/**
 * Project config service.
 * An instance of the ProjectConfig service is globally accessible in Craft via [[\craft\base\ApplicationTrait::ProjectConfig()|<code>Craft::$app->projectConfig</code>]].
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.0
 */
class ProjectConfig extends Component
{
    // Constants
    // =========================================================================

    // Cache settings
    // -------------------------------------------------------------------------
    const CACHE_KEY = 'project.config.files';
    const CACHE_DURATION = 60 * 60 * 24 * 30;

    // Config entities
    // -------------------------------------------------------------------------
    const ENTITY_SITES = 'sites';
    const ENTITY_SECTIONS = 'sections';
    const ENTITY_FIELDS = 'fields';
    const ENTITY_VOLUMES = 'volumes';

    /**
     * Whether there is an update pending based on config and snapshot.
     *
     * @return bool
     */
    public function isUpdatePending(): bool
    {
        $configSnapshot = $this->generateSnapshotFromConfigFiles();
        $currentSnapshot = $this->getCurrentSnapshot();

        $flatConfig = [];
        $flatCurrent = [];

        unset($configSnapshot['imports']);

        // flatten both snapshots so we can compare them.

        $flatten = function ($array, $path, &$result) use (&$flatten) {
            foreach ($array as $key => $value) {
                $thisPath = $path.'#'.$key;

                if (is_array($value)) {
                    $flatten($value, $thisPath, $result);
                    $result[$thisPath] = '.';
                } else {
                    $result[$thisPath] = $value;
                }
            }
        };

        $flatten($configSnapshot, '', $flatConfig);
        $flatten($currentSnapshot, '', $flatCurrent);


        foreach ($flatConfig as $key => $value) {
            if (!array_key_exists($key, $flatCurrent) || $flatCurrent[$key] !== $value) {
                return true;
            }
            unset($flatCurrent[$key]);
        }

        return !empty($flatCurrent);
    }

    /**
     * Get config file modified dates.
     *
     * @return array
     */
    public function getConfigFileModifyDates(): array
    {
        $cachedTimes = Craft::$app->getCache()->get(self::CACHE_KEY);

        if (!$cachedTimes) {
            return [];
        }

        $this->updateDateModifiedCache($cachedTimes);

        return $cachedTimes;
    }

    /**
     * Update config file modified date cache. If no modified dates passed, the config file tree will be parsed
     * to figure out the modified dates.
     *
     * @param array|null $fileList
     * @return bool
     */
    public function updateDateModifiedCache(array $fileList = null): bool
    {
        if (!$fileList) {
            $fileList = $this->getCurrentConfigFileList();
        }

        return Craft::$app->getCache()->set(self::CACHE_KEY, $fileList, self::CACHE_DURATION);
    }

    /**
     * Retrieve a a config file tree with modified times based on the main `system.yml` configuration file.
     *
     * @return array
     */
    public function getCurrentConfigFileList(): array
    {
        $basePath = Craft::$app->getPath()->getConfigPath();
        $baseFile = $basePath.'/system.yml';
        $fileList = [];

        $traverseFile = function($filePath) use (&$traverseFile, &$fileList) {
            $fileList[$filePath] = FileHelper::lastModifiedTime($filePath);

            $config = Yaml::parseFile($filePath);
            $fileDir = pathinfo($filePath, PATHINFO_DIRNAME);

            if (isset($config['imports'])) {
                foreach ($config['imports'] as $file) {
                    if (PathHelper::ensurePathIsContained($file)) {
                        $traverseFile($fileDir.'/'.$file);
                    }
                }
            }
        };

        $traverseFile($baseFile);

        return $fileList;
    }

    /**
     * Generate the configuration snapshot based on the configuration files.
     *
     * @return array
     */
    public function generateSnapshotFromConfigFiles(): array
    {
        $basePath = Craft::$app->getPath()->getConfigPath();
        $baseFile = $basePath.'/system.yml';

        $snapshot = [];

        $traverseFile = function($filePath) use (&$traverseFile, &$snapshot) {
            $config = Yaml::parseFile($filePath);
            $snapshot = array_merge($snapshot, $config);
            $fileDir = pathinfo($filePath, PATHINFO_DIRNAME);

            if (isset($config['imports'])) {
                foreach ($config['imports'] as $file) {
                    if (PathHelper::ensurePathIsContained($file)) {
                        $traverseFile($fileDir.'/'.$file);
                    }
                }
            }
        };

        $traverseFile($baseFile);

        return $snapshot;
    }

    /**
     * Get the stored snapshot.
     *
     * @return array
     * @throws \yii\web\ServerErrorHttpException
     */
    public function getCurrentSnapshot(): array
    {
        return unserialize(Craft::$app->getInfo()->configSnapshot, ['allowed_classes' => false]);
    }
}
