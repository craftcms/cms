<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m191222_002848_peer_asset_permissions migration.
 */
class m191222_002848_peer_asset_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Establish all the new permissions we should be looking for,
        // mapped to the old permissions users/groups must have to gain the new ones
        $newPermissions = [];
        foreach (Craft::$app->getVolumes()->getAllVolumes() as $volume) {
            $suffix = ":{$volume->uid}";
            $newPermissions += [
                "viewvolume{$suffix}" => "viewpeerfilesinvolume{$suffix}",
                "saveassetinvolume{$suffix}" => "editpeerfilesinvolume{$suffix}",
                "deletefilesandfoldersinvolume{$suffix}" => ["deletepeerfilesinvolume{$suffix}", "replacepeerfilesinvolume{$suffix}"],
                "editimagesinvolume{$suffix}" => "editpeerimagesinvolume{$suffix}",
            ];
        }

        // Now add the new permissions to existing users where applicable
        foreach ($newPermissions as $oldPermission => $newPermission) {
            $userIds = (new Query())
                ->select(['upu.userId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                ->where(['up.name' => $oldPermission])
                ->column($this->db);
            if (!empty($userIds)) {
                $insert = [];
                foreach ((array)$newPermission as $name) {
                    $this->insert(Table::USERPERMISSIONS, [
                        'name' => $name,
                    ]);
                    $newPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);
                    foreach ($userIds as $userId) {
                        $insert[] = [$newPermissionId, $userId];
                    }
                }
                $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
            }
        }

        // Don't make the same config changes twice
        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);
        if (version_compare($schemaVersion, '3.4.6', '<')) {
            foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                $groupPermissions = array_flip($group['permissions'] ?? []);
                $changed = false;
                foreach ($newPermissions as $oldPermission => $newPermission) {
                    if (isset($groupPermissions[$oldPermission])) {
                        foreach ((array)$newPermission as $name) {
                            $groupPermissions[$name] = true;
                        }
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
        echo "m191222_002848_peer_asset_permissions cannot be reverted.\n";
        return false;
    }
}
