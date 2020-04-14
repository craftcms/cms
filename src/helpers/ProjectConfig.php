<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\services\Fields;
use craft\services\ProjectConfig as ProjectConfigService;
use craft\services\Sites;
use craft\services\UserGroups;
use yii\base\InvalidConfigException;

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
     * Ensures all field config changes are processed immediately in a safe manner.
     */
    public static function ensureAllFieldsProcessed()
    {
        if (static::$_processedFields) {
            return;
        }
        static::$_processedFields = true;

        $projectConfig = Craft::$app->getProjectConfig();
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
     */
    public static function ensureAllSitesProcessed()
    {
        if (static::$_processedSites) {
            return;
        }
        static::$_processedSites = true;

        $projectConfig = Craft::$app->getProjectConfig();
        $allGroups = $projectConfig->get(Sites::CONFIG_SITEGROUP_KEY, true) ?? [];
        $allSites = $projectConfig->get(Sites::CONFIG_SITES_KEY, true) ?? [];

        foreach ($allGroups as $groupUid => $groupData) {
            // Ensure group is processed
            $projectConfig->processConfigChanges(Sites::CONFIG_SITEGROUP_KEY . '.' . $groupUid);
        }

        foreach ($allSites as $siteUid => $siteData) {
            // Ensure site is processed
            $projectConfig->processConfigChanges(Sites::CONFIG_SITES_KEY . '.' . $siteUid);
        }
    }

    /**
     * Ensure all user group config changes are processed immediately in a safe manner.
     */
    public static function ensureAllUserGroupsProcessed()
    {
        if (static::$_processedUserGroups) {
            return;
        }
        static::$_processedUserGroups = true;

        $projectConfig = Craft::$app->getProjectConfig();
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
     * Resets the static memoization variables.
     *
     * @return void
     */
    public static function reset()
    {
        static::$_processedFields = false;
        static::$_processedSites = false;
        static::$_processedUserGroups = false;
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
        $remove = [];

        foreach ($config as $key => &$value) {
            // Only scalars, arrays and simple objects allowed.
            if ($value instanceof \StdClass) {
                $value = (array)$value;
            }

            if (!empty($value) && !is_scalar($value) && !is_array($value)) {
                Craft::info('Unexpected data encountered in config data - ' . print_r($value, true));

                throw new InvalidConfigException('Unexpected data encountered in config data');
            }

            if (is_array($value)) {
                $value = static::cleanupConfig($value);

                if (empty($value)) {
                    $remove[] = $key;
                }
            }
        }

        unset($value);

        // Remove empty stuff
        foreach ($remove as $removeKey) {
            unset($config[$removeKey]);
        }

        ksort($config);

        return $config;
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

        // Only pack this array if its keys are not in numerical order
        if (ArrayHelper::isOrdered($array)) {
            return $array;
        }

        $packed = [];
        foreach ($array as $key => &$value) {
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
}
