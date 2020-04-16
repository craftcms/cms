<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;

/**
 * m190417_085010_add_image_editor_permissions migration.
 */
class m190417_085010_add_image_editor_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $projectConfig = Craft::$app->getProjectConfig();

        $volumes = $projectConfig->get('volumes');

        if (is_array($volumes)) {
            $groups = $projectConfig->get('users.groups');

            // Create all the new permissions in the table and store ids by name
            $volumeUids = array_keys($volumes);

            $permissionIdsByName = [];

            foreach ($volumeUids as $volumeUid) {
                $permissionName = 'editimagesinvolume:' . $volumeUid;

                if (empty($permissionIdsByName[$permissionName])) {
                    $this->insert(Table::USERPERMISSIONS, ['name' => $permissionName]);

                    $permissionIdsByName[$permissionName] = (new Query())
                        ->select(['id'])
                        ->from([Table::USERPERMISSIONS])
                        ->where(['name' => $permissionName])
                        ->scalar();
                }
            }

            // Migrate all the group permissions
            if (is_array($groups)) {
                $groupUids = array_keys($groups);

                // Collect eligible permission combos for groups
                $newPermissions = [];
                foreach ($groupUids as $groupUid) {
                    $permissions = $projectConfig->get('users.groups.' . $groupUid . '.permissions');

                    // If user group permissions are defined
                    if ($permissions !== null) {
                        foreach ($volumeUids as $volumeUid) {
                            if (in_array('saveassetinvolume:' . $volumeUid, $permissions, true) &&
                                in_array('deletefilesandfoldersinvolume:' . $volumeUid, $permissions, true)) {
                                $permissionName = 'editimagesinvolume:' . $volumeUid;
                                $newPermissions[$groupUid][] = $permissionName;
                            }
                        }
                    }
                }

                $projectConfig->muteEvents = true;
                // Add new permissions
                foreach ($newPermissions as $groupUid => $volumePermissions) {
                    $schemaVersion = $projectConfig->get('system.schemaVersion', true);

                    // If schema permits, update the DB
                    if (version_compare($schemaVersion, '3.2.3', '<=')) {
                        $permissions = array_merge($projectConfig->get('users.groups.' . $groupUid . '.permissions'), $volumePermissions);
                        $projectConfig->set('users.groups.' . $groupUid . '.permissions', $permissions);
                    }

                    // Update the DB
                    foreach ($volumePermissions as $permission) {
                        $this->insert(Table::USERPERMISSIONS_USERGROUPS, [
                            'permissionId' => $permissionIdsByName[$permission],
                            'groupId' => Db::idByUid(Table::USERGROUPS, $groupUid)
                        ]);
                    }
                }
                $projectConfig->muteEvents = false;

                // Migrate the users
                foreach ($volumeUids as $volumeUid) {
                    $savePermission = 'saveassetinvolume:' . $volumeUid;
                    $deletePermission = 'deletefilesandfoldersinvolume:' . $volumeUid;

                    // Get all the eligible users
                    $userIds = (new Query())
                        ->select(['users.id'])
                        ->from(['users' => Table::USERS])
                        ->innerJoin(['saveUserPermissions' => Table::USERPERMISSIONS_USERS], '[[saveUserPermissions.userId]] = [[users.id]]')
                        ->innerJoin(['saveInVolume' => Table::USERPERMISSIONS], [
                            'and',
                            '[[saveInVolume.id]] = [[saveUserPermissions.permissionId]]',
                            ['saveInVolume.name' => $savePermission]
                        ])
                        ->innerJoin(['deleteUserPermissions' => Table::USERPERMISSIONS_USERS], '[[deleteUserPermissions.userId]] = [[users.id]]')
                        ->innerJoin(['deleteInVolume' => Table::USERPERMISSIONS], [
                            'and',
                            '[[deleteInVolume.id]] = [[deleteUserPermissions.permissionId]]',
                            ['deleteInVolume.name' => $deletePermission]
                        ])
                        ->column();

                    $permissionRows = [];

                    foreach ($userIds as $userId) {
                        $permissionRows[] = [$userId, $permissionIdsByName['editimagesinvolume:' . $volumeUid]];
                    }

                    if (!empty($permissionRows)) {
                        $this->batchInsert(Table::USERPERMISSIONS_USERS, ['userId', 'permissionId'], $permissionRows);
                    }
                }
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190417_085010_add_image_editor_permissions cannot be reverted.\n";
        return false;
    }
}
