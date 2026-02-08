<?php

declare(strict_types=1);

namespace CraftCms\Cms\ProjectConfig;

use Craft;
use craft\helpers\DateTimeHelper;
use craft\helpers\FileHelper;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Diff;
use CraftCms\Cms\Support\Facades\Fields;
use CraftCms\Cms\Support\Json as JsonHelper;
use CraftCms\Cms\Support\Str;
use CraftCms\DependencyAwareCache\Dependency\AllDependencies;
use CraftCms\DependencyAwareCache\Dependency\CallbackDependency;
use CraftCms\DependencyAwareCache\Facades\DependencyCache;
use Illuminate\Support\Facades\Log;
use StdClass;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

final class ProjectConfigHelper
{
    /**
     * Returns a project config compatible value encoded for storage.
     */
    public static function encodeValueAsString(mixed $value): string
    {
        return JsonHelper::encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
    }

    /**
     * @var bool Whether we've already processed all filesystem configs.
     *
     * @see ensureAllFilesystemsProcessed()
     */
    private static bool $_processedFilesystems = false;

    /**
     * @var bool Whether we've already processed all field configs.
     *
     * @see ensureAllFieldsProcessed()
     */
    private static bool $_processedFields = false;

    /**
     * @var bool Whether we've already processed all site configs.
     *
     * @see ensureAllSitesProcessed()
     */
    private static bool $_processedSites = false;

    /**
     * @var bool Whether we've already processed all user group configs.
     *
     * @see ensureAllUserGroupsProcessed()
     */
    private static bool $_processedUserGroups = false;

    /**
     * @var bool Whether we've already processed all entry type configs.
     *
     * @see ensureAllEntryTypesProcessed()
     */
    private static bool $_processedEntryTypes = false;

    /**
     * @var bool Whether we've already processed all section configs.
     *
     * @see ensureAllSectionsProcessed()
     */
    private static bool $_processedSections = false;

    /**
     * @var bool Whether we've already processed all GraphQL schemas.
     *
     * @see ensureAllGqlSchemasProcessed()
     */
    private static bool $_processedGqlSchemas = false;

    /**
     * Ensures all filesystem config changes are processed immediately in a safe manner.
     */
    public static function ensureAllFilesystemsProcessed(): void
    {
        $projectConfig = app(ProjectConfig::class);

        if (self::$_processedFilesystems || ! $projectConfig->isApplyingExternalChanges) {
            return;
        }

        self::$_processedFilesystems = true;
        $projectConfig->processConfigChanges(ProjectConfig::PATH_FS);
    }

    /**
     * Ensures all field config changes are processed immediately in a safe manner.
     */
    public static function ensureAllFieldsProcessed(): void
    {
        self::ensureAllFilesystemsProcessed();

        $projectConfig = app(ProjectConfig::class);

        if (self::$_processedFields || ! $projectConfig->isApplyingExternalChanges) {
            return;
        }

        self::$_processedFields = true;

        $allFields = $projectConfig->get(ProjectConfig::PATH_FIELDS, true) ?? [];

        foreach ($allFields as $fieldUid => $fieldData) {
            // Ensure field is processed
            $projectConfig->processConfigChanges(ProjectConfig::PATH_FIELDS.'.'.$fieldUid);
        }

        // Now that all fields are processed, invalidate the field handle caches
        // so they are rebuilt with any overridden field handles in field layouts.
        app(\CraftCms\Cms\Field\Fields::class)->invalidateCaches();
    }

    /**
     * Ensure all site config changes are processed immediately in a safe manner.
     *
     * @param  bool  $force  Whether to proceed even if YAML changes are not currently being applied
     */
    public static function ensureAllSitesProcessed(bool $force = false): void
    {
        $projectConfig = app(ProjectConfig::class);

        if (self::$_processedSites || (! $force && ! $projectConfig->isApplyingExternalChanges)) {
            return;
        }

        self::$_processedSites = true;

        $allGroups = $projectConfig->get(ProjectConfig::PATH_SITE_GROUPS, true) ?? [];
        $allSites = $projectConfig->get(ProjectConfig::PATH_SITES, true) ?? [];

        foreach ($allGroups as $groupUid => $groupData) {
            // Ensure group is processed
            $projectConfig->processConfigChanges(ProjectConfig::PATH_SITE_GROUPS.'.'.$groupUid, $force);
        }

        foreach ($allSites as $siteUid => $siteData) {
            // Ensure site is processed
            $projectConfig->processConfigChanges(ProjectConfig::PATH_SITES.'.'.$siteUid, $force);
        }
    }

    /**
     * Ensure all user group config changes are processed immediately in a safe manner.
     */
    public static function ensureAllUserGroupsProcessed(): void
    {
        $projectConfig = app(ProjectConfig::class);

        if (self::$_processedUserGroups || ! $projectConfig->isApplyingExternalChanges) {
            return;
        }

        self::$_processedUserGroups = true;

        $allGroups = $projectConfig->get(ProjectConfig::PATH_USER_GROUPS, true);

        if (is_array($allGroups)) {
            foreach ($allGroups as $groupUid => $groupData) {
                $path = ProjectConfig::PATH_USER_GROUPS.'.';
                // Ensure group is processed
                $projectConfig->processConfigChanges($path.$groupUid);
            }
        }
    }

    /**
     * Ensure all entry type config changes are processed immediately in a safe manner.
     */
    public static function ensureAllEntryTypesProcessed(): void
    {
        $projectConfig = app(ProjectConfig::class);

        if (self::$_processedEntryTypes || ! $projectConfig->isApplyingExternalChanges) {
            return;
        }

        self::$_processedEntryTypes = true;

        $configs = $projectConfig->get(ProjectConfig::PATH_ENTRY_TYPES, true) ?? [];
        foreach ($configs as $uid => $config) {
            $path = sprintf('%s.%s', ProjectConfig::PATH_ENTRY_TYPES, $uid);
            $projectConfig->processConfigChanges($path);
        }
    }

    /**
     * Ensure all section config changes are processed immediately in a safe manner.
     */
    public static function ensureAllSectionsProcessed(): void
    {
        $projectConfig = app(ProjectConfig::class);

        if (self::$_processedSections || ! $projectConfig->isApplyingExternalChanges) {
            return;
        }

        self::$_processedSections = true;

        $allSections = $projectConfig->get(ProjectConfig::PATH_SECTIONS, true);

        if (is_array($allSections)) {
            foreach ($allSections as $sectionUid => $sectionData) {
                $path = ProjectConfig::PATH_SECTIONS.'.';
                // Ensure section is processed
                $projectConfig->processConfigChanges($path.$sectionUid);
            }
        }
    }

    /**
     * Ensure all GraphQL schema config changes are processed immediately in a safe manner.
     */
    public static function ensureAllGqlSchemasProcessed(): void
    {
        $projectConfig = app(ProjectConfig::class);

        if (self::$_processedGqlSchemas || ! $projectConfig->isApplyingExternalChanges) {
            return;
        }

        self::$_processedGqlSchemas = true;

        $allSchemas = $projectConfig->get(ProjectConfig::PATH_GRAPHQL_SCHEMAS, true);

        if (is_array($allSchemas)) {
            foreach ($allSchemas as $schemaUid => $schema) {
                $path = ProjectConfig::PATH_GRAPHQL_SCHEMAS.'.';
                // Ensure schema is processed
                $projectConfig->processConfigChanges($path.$schemaUid);
            }
        }
    }

    /**
     * Resets the static memoization variables.
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
     * @param  array  $config  Config array to clean
     *
     * @throws InvalidConfigException if config contains unexpected data.
     */
    public static function cleanupConfig(array $config): array
    {
        $cleanConfig = [];

        foreach ($config as $key => $value) {
            $value = self::_cleanupConfigValue($value);

            // Ignore empty arrays
            if (! is_array($value) || ! empty($value)) {
                $cleanConfig[$key] = $value;
            }
        }

        ksort($cleanConfig, SORT_NATURAL);

        return $cleanConfig;
    }

    /**
     * Cleans a config value.
     *
     * @throws InvalidConfigException
     */
    private static function _cleanupConfigValue(mixed $value): mixed
    {
        // Only scalars, arrays and simple objects allowed.
        if ($value instanceof StdClass) {
            $value = (array) $value;
        }

        if (! empty($value) && ! is_scalar($value) && ! is_array($value)) {
            Log::info('Unexpected data encountered in config data - '.print_r($value, true));
            throw new InvalidConfigException('Unexpected data encountered in config data');
        }

        if (is_array($value)) {
            // Is this a packed array?
            if (isset($value[ProjectConfig::ASSOC_KEY])) {
                $cleanPackedArray = [];

                foreach ($value[ProjectConfig::ASSOC_KEY] as $pKey => $pArray) {
                    // Make sure it has a value
                    if (isset($pArray[1])) {
                        $pArray[1] = self::_cleanupConfigValue($pArray[1]);

                        // Ignore empty arrays
                        if (! is_array($pArray[1]) || ! empty($pArray[1])) {
                            $cleanPackedArray[$pKey] = $pArray;
                        }
                    }
                }

                if (! empty($cleanPackedArray)) {
                    ksort($cleanPackedArray, SORT_NATURAL);
                    $value[ProjectConfig::ASSOC_KEY] = $cleanPackedArray;
                } else {
                    // Set $value to an empty array so it doesn't make it into the final config
                    $value = [];
                }
            } else {
                $value = self::cleanupConfig($value);
            }
        }

        return $value;
    }

    /**
     * Loops through an array, and prepares any nested associative arrays for storage in project config,
     * so that the order of its items will be remembered.
     *
     * @param  bool  $recursive  Whether to process nested associative arrays as well
     */
    public static function packAssociativeArrays(array $array, bool $recursive = true): array
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = self::packAssociativeArray($value, $recursive);
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
     * @param  bool  $recursive  Whether to process nested associative arrays as well
     */
    public static function packAssociativeArray(array $array, bool $recursive = true): array
    {
        // Deal with the nested values first
        if ($recursive) {
            foreach ($array as &$value) {
                if (is_array($value)) {
                    $value = self::packAssociativeArray($value, true);
                }
            }
        }
        unset($value);

        // Only pack this array if its keys are not in numerical order
        if (Arr::isOrdered($array)) {
            return $array;
        }

        // Make sure this isn't already packed
        if (isset($array[ProjectConfig::ASSOC_KEY])) {
            Log::warning('Attempting to pack an already-packed associative array.');

            return $array;
        }

        $packed = [];
        foreach ($array as $key => $value) {
            $packed[] = [$key, $value];
        }

        return [ProjectConfig::ASSOC_KEY => $packed];
    }

    /**
     * Loops through an array, and restores any arrays that were prepared via [[packAssociativeArray()]]
     * to their original form.
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
     * @param  bool  $recursive  Whether to process nested associative arrays as well
     */
    public static function unpackAssociativeArray(array $array, bool $recursive = true): array
    {
        if (isset($array[ProjectConfig::ASSOC_KEY])) {
            $associative = [];
            if (! empty($array[ProjectConfig::ASSOC_KEY])) {
                foreach ($array[ProjectConfig::ASSOC_KEY] as $items) {
                    if (! array_key_exists(0, $items) || ! array_key_exists(1, $items)) {
                        Log::warning('Skipping incomplete packed associative array data', [__METHOD__]);

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
                    $value = self::unpackAssociativeArray($value, true);
                }
            }
        }

        return $array;
    }

    /**
     * Flatten a config array to a dot.based.key array.
     */
    public static function flattenConfigArray(array $array, string $path, array &$result): void
    {
        foreach ($array as $key => $value) {
            $thisPath = ltrim($path.'.'.$key, '.');

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
     * @return array in the form of [$file => $config], where `$file` is the relative config file path in Project Config folder
     */
    public static function splitConfigIntoComponents(array $config): array
    {
        $splitConfig = [];
        self::splitConfigIntoComponentsInternal($config, $splitConfig);

        // Store whatever's left in project.yaml
        $splitConfig[ProjectConfig::CONFIG_FILENAME] = $config;

        return $splitConfig;
    }

    /**
     * Traverse a nested data array according to path and perform an action depending on parameters.
     *
     * @param  array  $data  A nested array of data to traverse
     * @param  string|string[]  $path  Path used to traverse the array. Either an array or a dot.based.path
     * @param  mixed  $value  Value to set at the destination. If null, will return the value, unless deleting
     * @param  bool  $delete  Whether to delete the value at the destination or not.
     */
    public static function traverseDataArray(array &$data, string|array $path, mixed $value = null, bool $delete = false): mixed
    {
        if (is_string($path)) {
            $path = explode('.', $path);
        }

        $nextSegment = array_shift($path);

        // Last segment?
        if (count($path) === 0) {
            // Delete
            if ($delete) {
                unset($data[$nextSegment]);

                return null;
            }

            // Get
            if ($value === null) {
                return $data[$nextSegment] ?? null;
            }

            // Set
            $data[$nextSegment] = $value;

            return null;
        }

        // Make sure the next segment exists and is an array
        if (! isset($data[$nextSegment]) || ! is_array($data[$nextSegment])) {
            // If we're just here to delete/get a value, return null
            if ($delete || $value === null) {
                return null;
            }

            $data[$nextSegment] = [];
        }

        return self::traverseDataArray($data[$nextSegment], $path, $value, $delete);
    }

    /**
     * Recursively looks for an array of component configs (sub-arrays indexed by UUIDs), within the given config array.
     *
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
                        $file = ($path ? "$path/" : '')."$key/$filename.yaml";
                        $splitConfig[$file] = $subConfig;
                    }
                    unset($config[$key]);
                    $split = true;
                } elseif (Arr::isAssoc($configData)) {
                    // Look deeper
                    $subpath = ($path ? "$path/" : '').$key;
                    if (self::splitConfigIntoComponentsInternal($configData, $splitConfig, $subpath)) {
                        $split = true;
                        // Store whatever's left in the same folder
                        if (! empty($configData)) {
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
     */
    private static function isComponentArray(array $item): bool
    {
        if (empty($item)) {
            return false;
        }

        return array_all($item, fn ($value, $key) => ! (! is_array($value) || ! is_string($key) || ! Str::isUuid($key)));
    }

    /**
     * Returns a diff of the pending project config YAML changes, compared to the currently loaded project config.
     *
     * @param  bool  $invert  Whether to reverse the diff, so the loaded config is treated as the source of truth
     */
    public static function diff(bool $invert = false): string
    {
        $projectConfig = app(ProjectConfig::class);
        $cacheKey = ProjectConfig::DIFF_CACHE_KEY.($invert ? ':reverse' : '');

        return DependencyCache::rememberForever($cacheKey, function () use ($projectConfig, $invert): string {
            $currentConfig = self::cleanupConfig($projectConfig->get());
            $pendingConfig = self::cleanupConfig($projectConfig->get(null, true));

            if ($invert) {
                return Diff::diff($pendingConfig, $currentConfig);
            }

            return Diff::diff($currentConfig, $pendingConfig);
        }, new AllDependencies([
            $projectConfig->getCacheDependency(),
            new CallbackDependency(fn (): string => md5(JsonHelper::encode(app(ProjectConfig::class)->get(null, true)))),
        ]));
    }

    /**
     * Updates the `dateModified` value in `config/project/project.yaml`.
     *
     * If a Git conflict is detected on the `dateModified` value, a conflict resolution will also be attempted.
     *
     * @param  int|null  $timestamp  The updated `dateModified` value. If `null`, the current time will be used.
     */
    public static function touch(?int $timestamp = null): void
    {
        if ($timestamp === null) {
            $timestamp = DateTimeHelper::currentTimeStamp();
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
                if (! $isTimestamp) {
                    $newContents .= $line;
                }

                continue;
            }

            if (! $isTimestamp) {
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
                        $newContents .= $mineMarker.$btMine.$conflictDl.$btTheirs.$theirsMarker;
                    }
                    if ($foundTimestampInConflict) {
                        $newContents .= $timestampLine;
                        if ($atMine || $atTheirs) {
                            $newContents .= $mineMarker.$atMine.$conflictDl.$atTheirs.$theirsMarker;
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

        if (! $foundTimestamp) {
            $newContents .= $timestampLine;
        }

        FileHelper::writeToFile($path, $newContents);
    }

    /**
     * Returns an array of the individual segments in a given project config path.
     *
     * @return string[]
     *
     * @throws InvalidArgumentException if `$path` is an empty string
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
     * @throws InvalidArgumentException if `$path` is an empty string
     */
    public static function lastPathSegment(string $path): string
    {
        $segments = self::pathSegments($path);

        return end($segments);
    }

    /**
     * Returns the given project config path with all but its last segment, or `null` if the path only had one segment.
     *
     * @throws InvalidArgumentException if `$path` is an empty string
     */
    public static function pathWithoutLastSegment(string $path): ?string
    {
        $segments = self::pathSegments($path);
        array_pop($segments);

        return ! empty($segments) ? implode('.', $segments) : null;
    }
}
