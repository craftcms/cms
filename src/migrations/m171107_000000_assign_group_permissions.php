<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m171107_000000_assign_group_permissions migration.
 */
class m171107_000000_assign_group_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // See which users & groups already have the "assignUserPermissions" permission
        $userIds = (new Query())
            ->select(['up_u.userId'])
            ->from(['{{%userpermissions_users}} up_u'])
            ->innerJoin('{{%userpermissions}} up', '[[up.id]] = [[up_u.permissionId]]')
            ->where(['up.name' => 'assignuserpermissions'])
            ->column($this->db);

        $groupIds = (new Query())
            ->select(['up_ug.groupId'])
            ->from(['{{%userpermissions_usergroups}} up_ug'])
            ->innerJoin('{{%userpermissions}} up', '[[up.id]] = [[up_ug.permissionId]]')
            ->where(['up.name' => 'assignuserpermissions'])
            ->column($this->db);

        if (empty($userIds) && empty($groupIds)) {
            return;
        }

        // Get the user group IDs
        $allGroupIds = (new Query())
            ->select(['id'])
            ->from(['{{%usergroups}}'])
            ->column();

        // Create the new permissions
        $permissionIds = [];

        $this->insert('{{%userpermissions}}', ['name' => 'assignusergroups']);
        $permissionIds[] = $this->db->getLastInsertID('{{%userpermissions}}');

        foreach ($allGroupIds as $groupId) {
            $this->insert('{{%userpermissions}}', ['name' => 'assignusergroup:'.$groupId]);
            $permissionIds[] = $this->db->getLastInsertID('{{%userpermissions}}');
        }

        // Assign the new permissions to the users
        if (!empty($userIds)) {
            $data = [];
            foreach ($userIds as $userId) {
                foreach ($permissionIds as $permissionId) {
                    $data[] = [$permissionId, $userId];
                }
            }
            $this->batchInsert('{{%userpermissions_users}}', ['permissionId', 'userId'], $data);
        }

        // Assign the new permissions to the groups
        if (!empty($groupIds)) {
            $data = [];
            foreach ($groupIds as $groupId) {
                foreach ($permissionIds as $permissionId) {
                    $data[] = [$permissionId, $groupId];
                }
            }
            $this->batchInsert('{{%userpermissions_usergroups}}', ['permissionId', 'groupId'], $data);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171107_000000_assign_group_permissions cannot be reverted.\n";
        return false;
    }
}
