<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m241125_122914_add_viewUsers_permission migration.
 */
class m241125_122914_add_viewUsers_permission extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $userIds = (new Query())
            ->select(['upu.userId'])
            ->from(['upu' => Table::USERPERMISSIONS_USERS])
            ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
            ->where(['up.name' => strtolower('editUsers')])
            ->column($this->db);

        $userIds = array_unique($userIds);
        $permissionName = $this->permissionName();

        if (!empty($userIds)) {
            $insert = [];

            $this->insert(Table::USERPERMISSIONS, [
                'name' => $permissionName,
            ]);
            $newPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);
            foreach ($userIds as $userId) {
                $insert[] = [$newPermissionId, $userId];
            }

            $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
        }

        $projectConfig = Craft::$app->getProjectConfig();
        foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
            $groupPermissions = array_flip($group['permissions'] ?? []);
            if (isset($groupPermissions[strtolower('editUsers')])) {
                $groupPermissions[$permissionName] = true;
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
        $permissionName = $this->permissionName();
        $permissionId = (new Query())
            ->select('id')
            ->from(Table::USERPERMISSIONS)
            ->where(['name' => $permissionName])
            ->scalar();

        if ($permissionId) {
            $this->delete(Table::USERPERMISSIONS_USERS, [
                'permissionId' => $permissionId,
            ]);
        }

        $projectConfig = Craft::$app->getProjectConfig();
        foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
            $groupPermissions = array_flip($group['permissions'] ?? []);

            if (isset($groupPermissions[$permissionName])) {
                unset($groupPermissions[$permissionName]);
                $projectConfig->set("users.groups.$uid.permissions", array_keys($groupPermissions));
            }
        }

        return true;
    }

    private function permissionName(): string
    {
        return strtolower('viewUsers');
    }
}
