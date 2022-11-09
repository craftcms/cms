<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\services\ProjectConfig as ProjectConfigService;
use StdClass;
use yii\base\InvalidArgumentException;
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
     * Returns a project config compatible value encoded for storage.
     *
     * @param mixed $value
     * @return string
     * @since 4.0.0
     */
    public static function encodeValueAsString(mixed $value): string
    {
        return Json::encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @var bool Whether we've already processed all filesystem configs.
     * @see ensureAllFilesystemsProcessed()
     */
    private static bool $_processedFilesystems = false;

    /**
     * @var bool Whether we've already processed all field configs.
     * @see ensureAllFieldsProcessed()
     */
    private static bool $_processedFields = false;

    /**
     * @var bool Whether we've already processed all site configs.
     * @see ensureAllSitesProcessed()
     */
    private static bool $_processedSites = false;

    /**
     * @var bool Whether we've already processed all user group configs.
     * @see ensureAllUserGroupsProcessed()
     */
    private static bool $_processedUserGroups = false;

    /**
     * @var bool Whether we've already processed all section configs.
     * @see ensureAllSectionsProcessed()
     */
    private static bool $_processedSections = false;

    /**
     * @var bool Whether we've already processed all GraphQL schemas.
     * @see ensureAllGqlSchemasProcessed()
     */
    private static bool $_processedGqlSchemas = false;

    /**
     * Ensures all filesystem config changes are processed immediately in a safe manner.
     *
     * @since 4.1.2
     */
    public static function ensureAllFilesystemsProcessed(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (self::$_processedFilesystems || !$projectConfig->getIsApplyingExternalChanges()) {
            return;
        }

        self::$_processedFilesystems = true;
        $projectConfig->processConfigChanges(ProjectConfigService::PATH_FS);
    }

    /**
     * Ensures all field config changes are processed immediately in a safe manner.
     */
    public static function ensureAllFieldsProcessed(): void
    {
        static::ensureAllFilesystemsProcessed();

        $projectConfig = Craft::$app->getProjectConfig();

        if (self::$_processedFields || !$projectConfig->getIsApplyingExternalChanges()) {
            return;
        }

        self::$_processedFields = true;

        $allGroups = $projectConfig->get(ProjectConfigService::PATH_FIELD_GROUPS, true) ?? [];
        $allFields = $projectConfig->get(ProjectConfigService::PATH_FIELDS, true) ?? [];

        foreach ($allGroups as $groupUid => $groupData) {
            // Ensure group is processed
            $projectConfig->processConfigChanges(ProjectConfigService::PATH_FIELD_GROUPS . '.' . $groupUid);
        }

        foreach ($allFields as $fieldUid => $fieldData) {
            // Ensure field is processed
            $projectConfig->processConfigChanges(ProjectConfigService::PATH_FIELDS . '.' . $fieldUid);
        }
    }

    /**
     * Ensure all site config changes are processed immediately in a safe manner.
     *
     * @param bool $force Whether to proceed even if YAML changes are not currently being applied
     */
    public static function ensureAllSitesProcessed(bool $force = false): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (self::$_processedSites || (!$force && !$projectConfig->getIsApplyingExternalChanges())) {
            return;
        }

        self::$_processedSites = true;

        $allGroups = $projectConfig->get(ProjectConfigService::PATH_SITE_GROUPS, true) ?? [];
        $allSites = $projectConfig->get(ProjectConfigService::PATH_SITES, true) ?? [];

        foreach ($allGroups as $groupUid => $groupData) {
            // Ensure group is processed
            $projectConfig->processConfigChanges(ProjectConfigService::PATH_SITE_GROUPS . '.' . $groupUid, $force);
        }

        foreach ($allSites as $siteUid => $siteData) {
            // Ensure site is processed
            $projectConfig->processConfigChanges(ProjectConfigService::PATH_SITES . '.' . $siteUid, $force);
        }
    }

    /**
     * Ensure all user group config changes are processed immediately in a safe manner.
     */
    public static function ensureAllUserGroupsProcessed(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (self::$_processedUserGroups || !$projectConfig->getIsApplyingExternalChanges()) {
            return;
        }

        self::$_processedUserGroups = true;

        $allGroups = $projectConfig->get(ProjectConfigService::PATH_USER_GROUPS, true);

        if (is_array($allGroups)) {
            foreach ($allGroups as $groupUid => $groupData) {
                $path = ProjectConfigService::PATH_USER_GROUPS . '.';
                // Ensure group is processed
                $projectConfig->processConfigChanges($path . $groupUid);
            }
        }
    }

    /**
     * Ensure all section config changes are processed immediately in a safe manner.
     *
     * @since 4.0.0
     */
    public static function ensureAllSectionsProcessed(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (self::$_processedSections || !$projectConfig->getIsApplyingExternalChanges()) {
            return;
        }

        self::$_processedSections = true;

        $allSections = $projectConfig->get(ProjectConfigService::PATH_SECTIONS, true);

        if (is_array($allSections)) {
            foreach ($allSections as $sectionUid => $sectionData) {
                $path = ProjectConfigService::PATH_SECTIONS . '.';
                // Ensure section is processed
                $projectConfig->processConfigChanges($path . $sectionUid);
            }
        }
    }

    /**
     * Ensure all GraphQL schema config changes are processed immediately in a safe manner.
     */
    public static function ensureAllGqlSchemasProcessed(): void
    {
        $projectConfig = Craft::$app->getProjectConfig();

        if (self::$_processedGqlSchemas || !$projectConfig->getIsApplyingExternalChanges()) {
            return;
        }

        self::$_processedGqlSchemas = true;

        $allSchemas = $projectConfig->get(ProjectConfigService::PATH_GRAPHQL_SCHEMAS, true);

        if (is_array($allSchemas)) {
            foreach ($allSchemas as $schemaUid => $schema) {
                $path = ProjectConfigService::PATH_GRAPHQL_SCHEMAS . '.';
                // Ensure schema is processed
                $projectConfig->processConfigChanges($path . $schemaUid);
            }
        }
    }

    /**
     * Resets the static memoization variables.
     *
     */
    public static function reset(): void
    {
        self::$_processedFields = false;
        self::$_processedSites = false;
        self::$_processedUserGroups = false;
        self::$_processedGqlSchemas = false;
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
    private static function _cleanupConfigValue(mixed $value): mixed
    {
        // Only scalars, arrays and simple objects allowed.
        if ($value instanceof StdClass) {
            $value = (array)$value;
        }

        if (!empty($value) && !is_scalar($value) && !is_array($value)) {
            Craft::info('Unexpected data encountered in config data - ' . print_r($value, true));
            throw new InvalidConfigException('Unexpected data encountered in config data');
        }

        if (is_array($value)) {
            // Is this a packed array?
            if (isset($value[ProjectConfigService::ASSOC_KEY])) {
                $cleanPackedArray = [];

                foreach ($value[ProjectConfigService::ASSOC_KEY] as $pKey => $pArray) {
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
                    $value[ProjectConfigService::ASSOC_KEY] = $cleanPackedArray;
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
        if (isset($array[ProjectConfigService::ASSOC_KEY])) {
            Craft::warning('Attempting to pack an already-packed associative array.');
            return $array;
        }

        $packed = [];
        foreach ($array as $key => $value) {
            $packed[] = [$key, $value];
        }
        return [ProjectConfigService::ASSOC_KEY => $packed];
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
        if (isset($array[ProjectConfigService::ASSOC_KEY])) {
            $associative = [];
            if (!empty($array[ProjectConfigService::ASSOC_KEY])) {
                foreach ($array[ProjectConfigService::ASSOC_KEY] as $items) {
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
     * @param array $array
     * @param string $path
     * @param array $result
     * @since 3.4.0
     */
    public static function flattenConfigArray(array $array, string $path, array &$result): void
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
     * Traverse a nested data array according to path and perform an action depending on parameters.
     *
     * @param array $data A nested array of data to traverse
     * @param string|string[] $path Path used to traverse the array. Either an array or a dot.based.path
     * @param mixed $value Value to set at the destination. If null, will return the value, unless deleting
     * @param bool $delete Whether to delete the value at the destination or not.
     * @return mixed
     * @since 4.0.0
     */
    public static function traverseDataArray(array &$data, string|array $path, mixed $value = null, bool $delete = false): mixed
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        }

        $nextSegment = array_shift($path);

        // Last piece?
        if (count($path) === 0) {
            if ($delete) {
                unset($data[$nextSegment]);
            } elseif ($value === null) {
                return $data[$nextSegment] ?? null;
            } else {
                $data[$nextSegment] = $value;
            }
        } else {
            if (!isset($data[$nextSegment])) {
                // If the path doesn't exist, it's fine if we wanted to delete or read
                if ($delete || $value === null) {
                    return null;
                }

                $data[$nextSegment] = [];
            } elseif (!is_array($data[$nextSegment])) {
                // If the next part is not an array, but we have to travel further, make it an array.
                $data[$nextSegment] = [];
            }

            return self::traverseDataArray($data[$nextSegment], $path, $value, $delete);
        }

        return null;
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
                    if (self::splitConfigIntoComponentsInternal($configData, $splitConfig, $subpath)) {
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
    private static function isComponentArray(array $item): bool
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
    public static function touch(?int $timestamp = null): void
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
        $mineMarker = null;
        $btMine = $atMine = $btTheirs = $atTheirs = null;
        $conflictDl = "=======\n";

        $newContents = '';

        while (($line = fgets($handle)) !== false) {
            $isTimestamp = str_starts_with($line, 'dateModified:');

            if ($foundTimestamp) {
                if (!$isTimestamp) {
                    $newContents .= $line;
                }
                continue;
            }

            if (!$isTimestamp) {
                if (str_starts_with($line, '<<<<<<<')) {
                    $mineMarker = $line;
                    $inMine = true;
                    $inTheirs = false;
                    $btMine = '';
                    continue;
                }

                if (str_starts_with($line, '=======')) {
                    $inMine = false;
                    $inTheirs = true;
                    $btTheirs = '';
                    continue;
                }

                if (str_starts_with($line, '>>>>>>>')) {
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

    /**
     * Returns an array of the individual segments in a given project config path.
     *
     * @param string $path
     * @return string[]
     * @throws InvalidArgumentException if `$path` is an empty string
     * @since 3.7.44
     */
    public static function pathSegments(string $path): array
    {
        if ($path === '') {
            throw new InvalidArgumentException('No project config path provided.');
        }
        return explode('.', $path);
    }

    /**
     * Returns the last segment in a given project config path.
     *
     * @param string $path
     * @return string|null
     * @throws InvalidArgumentException if `$path` is an empty string
     * @since 3.7.44
     */
    public static function lastPathSegment(string $path): ?string
    {
        $segments = static::pathSegments($path);
        return end($segments);
    }

    /**
     * Returns the given project config path with all but its last segment, or `null` if the path only had one segment.
     *
     * @param string $path
     * @return string|null
     * @throws InvalidArgumentException if `$path` is an empty string
     * @since 3.7.44
     */
    public static function pathWithoutLastSegment(string $path): ?string
    {
        $segments = static::pathSegments($path);
        array_pop($segments);
        return !empty($segments) ? implode('.', $segments) : null;
    }
}
