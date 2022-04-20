<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\services\ProjectConfig;

/**
 * m220123_213619_update_permissions migration.
 */
class m220123_213619_update_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $map = [];
        $delete = [
            'assignUserGroups',
            'customizeSources',
        ];

        $projectConfig = Craft::$app->getProjectConfig();
        $volumeConfigs = $projectConfig->get(ProjectConfig::PATH_VOLUMES, true) ?? [];
        $categoryGroupConfigs = $projectConfig->get(ProjectConfig::PATH_CATEGORY_GROUPS, true) ?? [];
        $sectionConfigs = $projectConfig->get(ProjectConfig::PATH_SECTIONS, true) ?? [];

        foreach (array_keys($volumeConfigs) as $volumeUid) {
            $map += [
                "viewVolume:$volumeUid" => ["viewAssets:$volumeUid"],
                "saveAssetInVolume:$volumeUid" => ["saveAssets:$volumeUid"],
                "deleteFilesAndFoldersInVolume:$volumeUid" => ["deleteAssets:$volumeUid"],
                "replaceFilesInVolume:$volumeUid" => ["replaceFiles:$volumeUid"],
                "editImagesInVolume:$volumeUid" => ["editImages:$volumeUid"],
                "viewPeerFilesInVolume:$volumeUid" => ["viewPeerAssets:$volumeUid"],
                "editPeerFilesInVolume:$volumeUid" => ["savePeerAssets:$volumeUid"],
                "replacePeerFilesInVolume:$volumeUid" => ["replacePeerFiles:$volumeUid"],
                "deletePeerFilesInVolume:$volumeUid" => ["deletePeerAssets:$volumeUid"],
                "editPeerImagesInVolume:$volumeUid" => ["editPeerImages:$volumeUid"],
                "createFoldersInVolume:$volumeUid" => ["createFolders:$volumeUid"],
            ];
        }

        foreach (array_keys($categoryGroupConfigs) as $groupUid) {
            $map += [
                "editCategories:$groupUid" => [
                    "viewCategories:$groupUid",
                    "saveCategories:$groupUid",
                    "deleteCategories:$groupUid",
                    "viewPeerCategoryDrafts:$groupUid",
                    "savePeerCategoryDrafts:$groupUid",
                    "deletePeerCategoryDrafts:$groupUid",
                ],
            ];
        }

        foreach (array_keys($sectionConfigs) as $sectionUid) {
            $map += [
                "editEntries:$sectionUid" => ["viewEntries:$sectionUid"],
                "publishEntries:$sectionUid" => ["saveEntries:$sectionUid"],
                "editPeerEntryDrafts:$sectionUid" => [
                    "viewPeerEntryDrafts:$sectionUid",
                    "savePeerEntryDrafts:$sectionUid",
                ],
                "editPeerEntries:$sectionUid" => ["viewPeerEntries:$sectionUid"],
                "publishPeerEntries:$sectionUid" => ["savePeerEntries:$sectionUid"],
            ];

            // Covered by saveEntries (and maybe savePeerEntries) + viewPeerEntryDrafts
            $delete[] = "publishPeerEntryDrafts:$sectionUid";
        }

        // Lowercase everything
        $map = array_combine(
            array_map('strtolower', array_keys($map)),
            array_map(fn($newPermissions) => array_map('strtolower', $newPermissions), array_values($map)));
        $delete = array_map('strtolower', $delete);

        // Now add the new permissions to existing users where applicable
        foreach ($map as $oldPermission => $newPermissions) {
            $userIds = (new Query())
                ->select(['upu.userId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                ->where(['up.name' => $oldPermission])
                ->column($this->db);

            $userIds = array_unique($userIds);

            if (!empty($userIds)) {
                $insert = [];

                foreach ($newPermissions as $newPermission) {
                    $this->insert(Table::USERPERMISSIONS, [
                        'name' => $newPermission,
                    ]);
                    $newPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);
                    foreach ($userIds as $userId) {
                        $insert[] = [$newPermissionId, $userId];
                    }
                }

                $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
            }

            $delete[] = $oldPermission;
        }

        $this->delete(Table::USERPERMISSIONS, [
            'name' => $delete,
        ]);

        // Don't make the same config changes twice
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '4.0.0', '<')) {
            foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                $groupPermissions = array_flip($group['permissions'] ?? []);

                foreach ($map as $oldPermission => $newPermissions) {
                    if (isset($groupPermissions[$oldPermission])) {
                        foreach ($newPermissions as $newPermission) {
                            $groupPermissions[$newPermission] = true;
                        }
                    }
                }

                foreach ($delete as $permission) {
                    if (isset($groupPermissions[$permission])) {
                        unset($groupPermissions[$permission]);
                    }
                }

                // assignUserGroup:<uid> permissions are explicitly required going forward
                $groupPermissions[strtolower("assignUserGroup:$uid")] = true;

                $projectConfig->set("users.groups.$uid.permissions", array_keys($groupPermissions));
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220123_213619_update_permissions cannot be reverted.\n";
        return false;
    }
}
