<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

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

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $map += [
                "viewVolume:$volume->uid" => ["viewAssets:$volume->uid"],
                "saveAssetInVolume:$volume->uid" => ["saveAssets:$volume->uid"],
                "deleteFilesAndFoldersInVolume:$volume->uid" => ["deleteAssets:$volume->uid"],
                "replaceFilesInVolume:$volume->uid" => ["replaceFiles:$volume->uid"],
                "editImagesInVolume:$volume->uid" => ["editImages:$volume->uid"],
                "viewPeerFilesInVolume:$volume->uid" => ["viewPeerAssets:$volume->uid"],
                "editPeerFilesInVolume:$volume->uid" => ["savePeerAssets:$volume->uid"],
                "replacePeerFilesInVolume:$volume->uid" => ["replacePeerFiles:$volume->uid"],
                "deletePeerFilesInVolume:$volume->uid" => ["deletePeerAssets:$volume->uid"],
                "editPeerImagesInVolume:$volume->uid" => ["editPeerImages:$volume->uid"],
                "createFoldersInVolume:$volume->uid" => ["createFolders:$volume->uid"],
            ];
        }

        foreach (Craft::$app->getCategories()->getAllGroups() as $group) {
            $map += [
                "editCategories:$group->uid" => [
                    "viewCategories:$group->uid",
                    "saveCategories:$group->uid",
                    "deleteCategories:$group->uid",
                    "viewPeerCategoryDrafts:$group->uid",
                    "savePeerCategoryDrafts:$group->uid",
                    "deletePeerCategoryDrafts:$group->uid",
                ],
            ];
        }

        foreach (Craft::$app->getSections()->getAllSections() as $section) {
            $map += [
                "editEntries:$section->uid" => ["viewEntries:$section->uid"],
                "publishEntries:$section->uid" => ["saveEntries:$section->uid"],
                "editPeerEntryDrafts:$section->uid" => [
                    "viewPeerEntryDrafts:$section->uid",
                    "savePeerEntryDrafts:$section->uid",
                ],
                "editPeerEntries:$section->uid" => ["viewPeerEntries:$section->uid"],
                "publishPeerEntries:$section->uid" => ["savePeerEntries:$section->uid"],
            ];

            // Covered by saveEntries (and maybe savePeerEntries) + viewPeerEntryDrafts
            $delete[] = "publishPeerEntryDrafts:$section->uid";
        }

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
        $projectConfig = Craft::$app->getProjectConfig();
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
                $groupPermissions["assignUserGroup:$uid"] = true;

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
