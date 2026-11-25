<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m260125_233614_changeAuthorForPeerEntries_permission migration.
 */
class m260125_233614_changeAuthorForPeerEntries_permission extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $projectConfig = Craft::$app->getProjectConfig();
        $map = [];

        foreach (Craft::$app->getEntries()->getAllSections() as $section) {
            $oldPermission = strtolower("savePeerEntries:$section->uid");
            $newPermission = strtolower("changeAuthorForPeerEntries:$section->uid");
            $map[$oldPermission] = $newPermission;
        }

        foreach ($map as $oldPermission => $newPermission) {
            $userIds = (new Query())
                ->select(['upu.userId'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                ->where(['up.name' => $oldPermission])
                ->column($this->db);

            $userIds = array_unique($userIds);

            if (!empty($userIds)) {
                $insert = [];

                // delete the permission + user/group assignments if it already exists for some reason
                $this->delete(Table::USERPERMISSIONS, [
                    'name' => $newPermission,
                ]);

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

        foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
            $groupPermissions = array_flip($group['permissions'] ?? []);
            $save = false;

            foreach ($map as $oldPermission => $newPermission) {
                if (isset($groupPermissions[$oldPermission])) {
                    $groupPermissions[$newPermission] = true;
                    $save = true;
                }
            }

            if ($save) {
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
        return true;
    }
}
