<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\services\Fields;
use craft\services\Gql as GqlService;
use craft\services\ProjectConfig as ProjectConfigService;
use craft\services\Sites;
use craft\services\UserGroups;
use yii\base\InvalidConfigException;
use yii\caching\ChainedDependency;
use yii\caching\ExpressionDependency;

/**
 * Class ProjectConfig
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1.0
 */
class ProjectConfig
{
    /**
     * @var bool Whether we've already processed all field configs.
     * @see ensureAllFieldsProcessed()
     */
    private static $_processedFields = false;

    /**
     * @var bool Whether we've already processed all site configs.
     * @see ensureAllSitesProcessed()
     */
    private static $_processedSites = false;

    /**
     * @var bool Whether we've already processed all user group configs.
     * @see ensureAllUserGroupsProcessed()
     */
    private static $_processedUserGroups = false;

    /**
     * @var bool Whether we've already processed all GraphQL schemas.
     * @see ensureAllGqlSchemasProcessed()
     */
    private static $_processedGqlSchemas = false;

    /**
     * Ensures all field config changes are processed immediately in a safe manner.
     */
    public static function ensureAllFieldsProcessed()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (static::$_processedFields || !$projectConfig->getIsApplyingYamlChanges()) {
            return;
        }

        static::$_processedFields = true;

        $allGroups = $projectConfig->get(Fields::CONFIG_FIELDGROUP_KEY, true) ?? [];
        $allFields = $projectConfig->get(Fields::CONFIG_FIELDS_KEY, true) ?? [];

        foreach ($allGroups as $groupUid => $groupData) {
            // Ensure group is processed
            $projectConfig->processConfigChanges(Fields::CONFIG_FIELDGROUP_KEY . '.' . $groupUid);
        }

        foreach ($allFields as $fieldUid => $fieldData) {
            // Ensure field is processed
            $projectConfig->processConfigChanges(Fields::CONFIG_FIELDS_KEY . '.' . $fieldUid);
        }
    }

    /**
     * Ensure all site config changes are processed immediately in a safe manner.
     *
     * @param bool $force Whether to proceed even if YAML changes are not currently being applied
     */
    public static function ensureAllSitesProcessed(bool $force = false)
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (static::$_processedSites || (!$force && !$projectConfig->getIsApplyingYamlChanges())) {
            return;
        }

        static::$_processedSites = true;

        $allGroups = $projectConfig->get(Sites::CONFIG_SITEGROUP_KEY, true) ?? [];
        $allSites = $projectConfig->get(Sites::CONFIG_SITES_KEY, true) ?? [];

        foreach ($allGroups as $groupUid => $groupData) {
            // Ensure group is processed
            $projectConfig->processConfigChanges(Sites::CONFIG_SITEGROUP_KEY . '.' . $groupUid, false, null, $force);
        }

        foreach ($allSites as $siteUid => $siteData) {
            // Ensure site is processed
            $projectConfig->processConfigChanges(Sites::CONFIG_SITES_KEY . '.' . $siteUid, false, null, $force);
        }
    }

    /**
     * Ensure all user group config changes are processed immediately in a safe manner.
     */
    public static function ensureAllUserGroupsProcessed()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (static::$_processedUserGroups || !$projectConfig->getIsApplyingYamlChanges()) {
            return;
        }

        static::$_processedUserGroups = true;

        $allGroups = $projectConfig->get(UserGroups::CONFIG_USERPGROUPS_KEY, true);

        if (is_array($allGroups)) {
            foreach ($allGroups as $groupUid => $groupData) {
                $path = UserGroups::CONFIG_USERPGROUPS_KEY . '.';
                // Ensure group is processed
                $projectConfig->processConfigChanges($path . $groupUid);
            }
        }
    }

    /**
     * Ensure all GraphQL schema config changes are processed immediately in a safe manner.
     */
    public static function ensureAllGqlSchemasProcessed()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (static::$_processedGqlSchemas || !$projectConfig->getIsApplyingYamlChanges()) {
            return;
        }

        static::$_processedGqlSchemas = true;

        $allSchemas = $projectConfig->get(GqlService::CONFIG_GQL_SCHEMAS_KEY, true);

        if (is_array($allSchemas)) {
            foreach ($allSchemas as $schemaUid => $schema) {
                $path = GqlService::CONFIG_GQL_SCHEMAS_KEY . '.';
                // Ensure schema is processed
                $projectConfig->processConfigChanges($path . $schemaUid);
            }
        }
    }

    /**
     * Resets the static memoization variables.
     *
     * @return null
     */
    public static function reset()
    {
        static::$_processedFields = false;
        static::$_processedSites = false;
        static::$_processedUserGroups = false;
        static::$_processedGqlSchemas = false;
    }

    /**
     * Traverse and clean a config array, removing empty values and sorting keys.
     *
     * @param array $config Config array to clean
     * @return array
     * @throws InvalidConfigException if config contains unexpected data.
     * @since 3.1.14
     */
    public static function cleanupConfig(array $config): array
    {
        $cleanConfig = [];

        foreach ($config as $key => $value) {
            $value = self::_cleanupConfigValue($value);

            // Ignore empty arrays
            if (!is_array($value) || !empty($value)) {
                $cleanConfig[$key] = $value;
            }
        }

        ksort($cleanConfig, SORT_NATURAL);
        return $cleanConfig;
    }

    /**
     * Cleans a config value.
     *
     * @param mixed $value
     * @return mixed
     * @throws InvalidConfigException
     */
    private static function _cleanupConfigValue($value)
    {
        // Only scalars, arrays and simple objects allowed.
        if ($value instanceof \StdClass) {
            $value = (array)$value;
        }

        if (!empty($value) && !is_scalar($value) && !is_array($value)) {
            Craft::info('Unexpected data encountered in config data - ' . print_r($value, true));
            throw new InvalidConfigException('Unexpected data encountered in config data');
        }

        if (is_array($value)) {
            // Is this a packed array?
            if (isset($value[ProjectConfigService::CONFIG_ASSOC_KEY])) {
                $cleanPackedArray = [];

                foreach ($value[ProjectConfigService::CONFIG_ASSOC_KEY] as $pKey => $pArray) {
                    // Make sure it has a value
                    if (isset($pArray[1])) {
                        $pArray[1] = self::_cleanupConfigValue($pArray[1]);

                        // Ignore empty arrays
                        if (!is_array($pArray[1]) || !empty($pArray[1])) {
                            $cleanPackedArray[$pKey] = $pArray;
                        }
                    }
                }

                if (!empty($cleanPackedArray)) {
                    ksort($cleanPackedArray, SORT_NATURAL);
                    $value[ProjectConfigService::CONFIG_ASSOC_KEY] = $cleanPackedArray;
                } else {
                    // Set $value to an empty array so it doesn't make it into the final config
                    $value = [];
                }
            } else {
                $value = static::cleanupConfig($value);
            }
        }

        return $value;
    }

    /**
     * Loops through an array, and prepares any nested associative arrays for storage in project config,
     * so that the order of its items will be remembered.
     *
     * @param array $array
     * @param bool $recursive Whether to process nested associative arrays as well
     * @return array
     * @since 3.4.0
     */
    public static function packAssociativeArrays(array $array, bool $recursive = true): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::packAssociativeArray($value, $recursive);
            }
        }

        return $array;
    }

    /**
     * Prepares an associative array for storage in project config, so that the order of its items will be remembered.
     *
     * ::: tip
     * Use [[unpackAssociativeArray()]] to restore the array to its original form when fetching the value from
     * the Project Config.
     * :::
     *
     * ---
     *
     * ```php
     * $myArray = [
     *     'foo' => 1,
     *     'bar' => 2,
     * ];
     *
     * // "Pack" the array so it doesn't get reordered to [bar=>2,foo=>1]
     * $packedArray = \craft\helpers\ProjectConfig::packAssociativeArray($myArray);
     *
     * Craft::$app->projectConfig->set($configKey, $packedArray);
     * ```
     *
     * @param array $array
     * @param bool $recursive Whether to process nested associative arrays as well
     * @return array
     * @since 3.4.0
     */
    public static function packAssociativeArray(array $array, bool $recursive = true): array
    {
        // Deal with the nested values first
        if ($recursive) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = static::packAssociativeArray($value, true);
                }
            }
        }
        unset($value);

        // Only pack this array if its keys are not in numerical order
        if (ArrayHelper::isOrdered($array)) {
            return $array;
        }

        // Make sure this isn't already packed
        if (isset($array[ProjectConfigService::CONFIG_ASSOC_KEY])) {
            Craft::warning('Attempting to pack an already-packed associative array.');
            return $array;
        }

        $packed = [];
        foreach ($array as $key => $value) {
            $packed[] = [$key, $value];
        }
        return [ProjectConfigService::CONFIG_ASSOC_KEY => $packed];
    }

    /**
     * Loops through an array, and restores any arrays that were prepared via [[packAssociativeArray()]]
     * to their original form.
     *
     * @param array $array
     * @return array
     * @since 3.4.0
     */
    public static function unpackAssociativeArrays(array $array): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::unpackAssociativeArray($value);
            }
        }

        return $array;
    }

    /**
     * Restores an array that was prepared via [[packAssociativeArray()]] to its original form.
     *
     * @param array $array
     * @param bool $recursive Whether to process nested associative arrays as well
     * @return array
     * @since 3.4.0
     */
    public static function unpackAssociativeArray(array $array, bool $recursive = true): array
    {
        if (isset($array[ProjectConfigService::CONFIG_ASSOC_KEY])) {
            $associative = [];
            if (!empty($array[ProjectConfigService::CONFIG_ASSOC_KEY])) {
                foreach ($array[ProjectConfigService::CONFIG_ASSOC_KEY] as $items) {
                    if (!isset($items[0], $items[1])) {
                        Craft::warning('Skipping incomplete packed associative array data', __METHOD__);
                        continue;
                    }
                    $associative[$items[0]] = $items[1];
                }
            }
            $array = $associative;
        }

        if ($recursive) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = static::unpackAssociativeArray($value, true);
                }
            }
        }

        return $array;
    }

    /**
     * Flatten a config array to a dot.based.key array.
     *
     * @param $array
     * @param $path
     * @param $result
     * @since 3.4.0
     */
    public static function flattenConfigArray($array, $path, &$result)
    {
        foreach ($array as $key => $value) {
            $thisPath = ltrim($path . '.' . $key, '.');

            if (is_array($value)) {
                self::flattenConfigArray($value, $thisPath, $result);
            } else {
                $result[$thisPath] = $value;
            }
        }
    }

    /**
     * Take a project config array and split it into components.
     * Components are defined per each second-level config entry, where all the sibling entries are keyed by UIDs.
     *
     * @param array $config
     * @return array in the form of [$file => $config], where `$file` is the relative config file path in Project Config folder
     * @since 3.5.0
     */
    public static function splitConfigIntoComponents(array $config): array
    {
        $splitConfig = [];
        self::splitConfigIntoComponentsInternal($config, $splitConfig);

        // Store whatever's left in project.yaml
        $splitConfig[ProjectConfigService::CONFIG_FILENAME] = $config;

        return $splitConfig;
    }

    /**
     * Recursively looks for an array of component configs (sub-arrays indexed by UUIDs), within the given config array.
     *
     * @param array $config
     * @param array $splitConfig
     * @param string|null $path
     * @return bool whether the config was split
     */
    private static function splitConfigIntoComponentsInternal(array &$config, array &$splitConfig, ?string $path = null): bool
    {
        $split = false;

        foreach ($config as $key => $configData) {
            if (is_array($configData)) {
                if (self::isComponentArray($configData)) {
                    foreach ($configData as $uid => $subConfig) {
                        // Does the sub config specify a handle?
                        if (isset($subConfig['handle']) && is_string($subConfig['handle']) && preg_match('/^\w+$/', $subConfig['handle'])) {
                            $filename = "{$subConfig['handle']}--$uid";
                        } else {
                            $filename = $uid;
                        }
                        $file = ($path ? "$path/" : '') . "$key/$filename.yaml";
                        $splitConfig[$file] = $subConfig;
                    }
                    unset($config[$key]);
                    $split = true;
                } elseif (ArrayHelper::isAssociative($configData)) {
                    // Look deeper
                    $subpath = ($path ? "$path/" : '') . $key;
                    if (static::splitConfigIntoComponentsInternal($configData, $splitConfig, $subpath)) {
                        $split = true;
                        // Store whatever's left in the same folder
                        if (!empty($configData)) {
                            $splitConfig["$subpath/$key.yaml"] = $configData;
                        }
                        unset($config[$key]);
                    }
                }
            }
        }

        return $split;
    }

    /**
     * Returns whether the given project config item is an array of component configs, where each key is a UUID, and each item is a sub-array.
     *
     * @param array $item
     * @return bool
     */
    private static function isComponentArray(array &$item): bool
    {
        if (empty($item)) {
            return false;
        }

        foreach ($item as $key => $value) {
            if (!is_array($value) || !is_string($key) || !StringHelper::isUUID($key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Returns a diff of the pending project config YAML changes, compared to the currently loaded project config.
     *
     * @param bool $invert Whether to reverse the diff, so the loaded config is treated as the source of truth
     * @return string
     * @since 3.5.6
     */
    public static function diff(bool $invert = false): string
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $cacheKey = ProjectConfigService::DIFF_CACHE_KEY . ($invert ? ':reverse' : '');

        return Craft::$app->getCache()->getOrSet($cacheKey, function() use ($projectConfig, $invert): string {
            $currentConfig = static::cleanupConfig($projectConfig->get());
            $pendingConfig = static::cleanupConfig($projectConfig->get(null, true));

            if ($invert) {
                return Diff::diff($pendingConfig, $currentConfig);
            }
            return Diff::diff($currentConfig, $pendingConfig);
        }, null, new ChainedDependency([
            'dependencies' => [
                $projectConfig->getCacheDependency(),
                new ExpressionDependency([
                    'expression' => 'md5(' . Json::class . '::encode(' . Craft::class . '::$app->getProjectConfig()->get(null, true)))',
                ]),
            ],
        ]));
    }

    /**
     * Updates the `dateModified` value in `config/project/project.yaml`.
     *
     * If a Git conflict is detected on the `dateModified` value, a conflict resolution will also be attempted.
     *
     * @param int|null $timestamp The updated `dateModified` value. If `null`, the current time will be used.
     * @since 3.5.14
     */
    public static function touch(?int $timestamp = null)
    {
        if ($timestamp === null) {
            $timestamp = time();
        }

        $timestampLine = "dateModified: $timestamp\n";

        $path = Craft::$app->getPath()->getProjectConfigFilePath();
        $handle = fopen($path, 'r');
        $foundTimestamp = false;

        // Conflict stuff. "bt" = "before timestamp"; "at" = "after timestamp"
        $inMine = $inTheirs = $foundTimestampInConflict = false;
        $mineMarker = $theirsMarker = null;
        $btMine = $atMine = $btTheirs = $atTheirs = null;
        $conflictDl = "=======\n";

        $newContents = '';

        while (($line = fgets($handle)) !== false) {
            $isTimestamp = strpos($line, 'dateModified:') === 0;

            if ($foundTimestamp) {
                if (!$isTimestamp) {
                    $newContents .= $line;
                }
                continue;
            }

            if (!$isTimestamp) {
                if (strpos($line, '<<<<<<<') === 0) {
                    $mineMarker = $line;
                    $inMine = true;
                    $inTheirs = false;
                    $btMine = '';
                    continue;
                }

                if (strpos($line, '=======') === 0) {
                    $inMine = false;
                    $inTheirs = true;
                    $btTheirs = '';
                    continue;
                }

                if (strpos($line, '>>>>>>>') === 0) {
                    $theirsMarker = $line;
                    // We've reached the end of the conflict
                    if ($btMine || $btTheirs) {
                        $newContents .= $mineMarker . $btMine . $conflictDl . $btTheirs . $theirsMarker;
                    }
                    if ($foundTimestampInConflict) {
                        $newContents .= $timestampLine;
                        if ($atMine || $atTheirs) {
                            $newContents .= $mineMarker . $atMine . $conflictDl . $atTheirs . $theirsMarker;
                        }
                        $foundTimestamp = true;
                    }
                    $inMine = $inTheirs = false;
                    $btMine = $atMine = $btTheirs = $atTheirs = null;
                    continue;
                }
            }

            if ($isTimestamp) {
                if ($inMine || $inTheirs) {
                    // Just start keeping track of the post-timestamp conflict
                    if ($inMine) {
                        $atMine = '';
                    } else {
                        $atTheirs = '';
                    }
                    $foundTimestampInConflict = true;
                } else {
                    $newContents .= $timestampLine;
                    $foundTimestamp = true;
                }
            } elseif ($inMine) {
                if ($atMine === null) {
                    $btMine .= $line;
                } else {
                    $atMine .= $line;
                }
            } elseif ($inTheirs) {
                if ($atTheirs === null) {
                    $btTheirs .= $line;
                } else {
                    $atTheirs .= $line;
                }
            } else {
                $newContents .= $line;
            }
        }

        fclose($handle);

        if (!$foundTimestamp) {
            $newContents .= $timestampLine;
        }

        FileHelper::writeToFile($path, $newContents);
    }
}
