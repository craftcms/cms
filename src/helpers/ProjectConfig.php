<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\models\Site;
use craft\services\Fields;
use craft\services\ProjectConfig as ProjectConfigService;
use craft\services\Sites;
use craft\services\UserGroups;
use Symfony\Component\Yaml\Yaml;


/**
 * Class ProjectConfig
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
 */
class ProjectConfig
{
    // Public Methods
    // =========================================================================

    /**
     * Generate the configuration map from an array of YAML files.
     * This function will ignore any `import` directives.
     *
     * @param array $fileList
     * @return array
     */
    public static function generateConfigMap(array $fileList): array
    {
        $nodes = [];

        foreach ($fileList as $file) {
            $config = Yaml::parseFile($file);

            // Take record of top nodes
            $topNodes = array_keys($config);
            foreach ($topNodes as $topNode) {
                $nodes[$topNode] = $file;
            }

        }

        unset($nodes['imports']);

        return $nodes;
    }

    /**
     * Ensure all field config changes are processed immediately in a safe manner.
     */
    public static function ensureAllFieldsProcessed()
    {
        static $alreadyProcessed = false;

        if ($alreadyProcessed) {
            return;
        }

        $alreadyProcessed = true;

        $projectConfig = Craft::$app->getProjectConfig();
        $allGroups = $projectConfig->get(Fields::CONFIG_FIELDGROUP_KEY, true);

        foreach ($allGroups as $groupUid => $groupData) {
            $path = Fields::CONFIG_FIELDGROUP_KEY.'.';
            // Ensure group is processed
            $projectConfig->processConfigChanges($path.$groupUid);

            foreach ($groupData[Fields::CONFIG_FIELDS_KEY] as $fieldUid => $fieldData) {
                // Ensure field is processed
                $projectConfig->processConfigChanges($path.$groupUid.'.'.Fields::CONFIG_FIELDS_KEY.'.'.$fieldUid);
            }
        }
    }

    /**
     * Ensure all site config changes are processed immediately in a safe manner.
     */
    public static function ensureAllSitesProcessed()
    {
        static $alreadyProcessed = false;

        if ($alreadyProcessed) {
            return;
        }

        $alreadyProcessed = true;

        $projectConfig = Craft::$app->getProjectConfig();
        $allGroups = $projectConfig->get(Sites::CONFIG_SITEGROUP_KEY, true);

        foreach ($allGroups as $groupUid => $groupData) {
            $path = Sites::CONFIG_SITEGROUP_KEY.'.';
            // Ensure group is processed
            $projectConfig->processConfigChanges($path.$groupUid);

            foreach ($groupData[Sites::CONFIG_SITES_KEY] as $siteUid => $siteData) {
                // Ensure site is processed
                $projectConfig->processConfigChanges($path.$groupUid.'.'.Sites::CONFIG_SITES_KEY.'.'.$siteUid);
            }
        }
    }

    /**
     * Ensure all site config changes are processed immediately in a safe manner.
     */
    public static function ensureAllUserGroupsProcessed()
    {
        static $alreadyProcessed = false;

        if ($alreadyProcessed) {
            return;
        }

        $alreadyProcessed = true;

        $projectConfig = Craft::$app->getProjectConfig();
        $allGroups = $projectConfig->get(UserGroups::CONFIG_USERPGROUPS_KEY, true);

        if (is_array($allGroups)) {
            foreach ($allGroups as $groupUid => $groupData) {
                $path = UserGroups::CONFIG_USERPGROUPS_KEY.'.';
                // Ensure group is processed
                $projectConfig->processConfigChanges($path.$groupUid);
            }
        }
    }
}
