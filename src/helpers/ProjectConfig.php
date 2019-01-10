<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\helpers;

use Craft;
use craft\services\Fields;
use craft\services\Sites;
use craft\services\UserGroups;


/**
 * Class ProjectConfig
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.1
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
}
