<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m200716_110900_replace_file_asset_permissions migration.
 */
class m200716_110900_replace_file_asset_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Establish all the permissions required to gain the new permission
        $permissionConditions = [];

        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $suffix = ":{$volume->uid}";
            $permissionConditions += [
                "replacefilesinvolume{$suffix}" => ["saveassetinvolume{$suffix}", "deletefilesandfoldersinvolume{$suffix}"]
            ];
        }

        // Now add the new permissions to existing users where applicable
        foreach ($permissionConditions as $newPermission => $oldPermissions) {
            // Make it a proper DB condition
            array_unshift($oldPermissions, 'and');

            $userIds = (new Query())
                ->select(['upu.userId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                ->where(['up.name' => $oldPermissions])
                ->column($this->db);

            $userIds = array_unique($userIds);

            if (!empty($userIds)) {
                $insert = [];
                $this->insert(Table::USERPERMISSIONS, [
                    'name' => $newPermission,
                ]);
                $newPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

                foreach ($userIds as $userId) {
                    $insert[] = [$newPermissionId, $userId];
                }

                $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
            }
        }

        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.5.10', '<')) {
            foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                $groupPermissions = array_flip($group['permissions'] ?? []);
                $changed = false;

                foreach ($permissionConditions as $newPermission => $oldPermissions) {
                    $exists = true;

                    foreach ($oldPermissions as $oldPermission) {
                        if (!isset($groupPermissions[$oldPermission])) {
                            $exists = false;
                            break;
                        }
                    }

                    if ($exists) {
                        $groupPermissions[$newPermission] = true;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $projectConfig->set("users.groups.{$uid}.permissions", array_keys($groupPermissions));
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200716_110900_replace_file_asset_permissions cannot be reverted.\n";
        return false;
    }
}
