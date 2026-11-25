<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\enums\PropagationMethod;

/**
 * m250522_090843_add_deleteEntriesForSite_and_deletePeerEntriesForSite_permissions migration.
 */
class m250522_090843_add_deleteEntriesForSite_and_deletePeerEntriesForSite_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $existingPermissions = array_keys($this->allPermissionNames());

        foreach ($existingPermissions as $existingPermission) {
            $newPermissionName = $this->newPermissionName($existingPermission);
            $existingPermissionName = strtolower($existingPermission) . ':';

            $records = (new Query())
                ->select(['upu.userId', 'up.name'])
                ->from(['upu' => Table::USERPERMISSIONS_USERS])
                ->innerJoin(['up' => Table::USERPERMISSIONS], '[[up.id]] = [[upu.permissionId]]')
                ->where(['like', 'up.name', $existingPermissionName])
                ->collect($this->db);

            $userIdsByPermission = [];
            foreach ($records as $record) {
                if (!isset($userIdsByPermission[$record['name']])) {
                    $userIdsByPermission[$record['name']] = [];
                }
                $userIdsByPermission[$record['name']][] = $record['userId'];
            }

            if (!empty($userIdsByPermission)) {
                foreach ($userIdsByPermission as $permission => $userIds) {
                    // get section uid
                    $sectionUid = str_replace($existingPermissionName, '', $permission);
                    $section = Craft::$app->getEntries()->getSectionByUid($sectionUid);
                    if ($section && $section->propagationMethod == PropagationMethod::Custom) {
                        $insert = [];

                        $this->insert(Table::USERPERMISSIONS, [
                            'name' => $newPermissionName . ':' . $sectionUid,
                        ]);
                        $newPermissionId = $this->db->getLastInsertID(Table::USERPERMISSIONS);

                        $userIds = array_unique($userIds);

                        foreach ($userIds as $userId) {
                            $insert[] = [$newPermissionId, $userId];
                        }

                        $this->batchInsert(Table::USERPERMISSIONS_USERS, ['permissionId', 'userId'], $insert);
                    }
                }
            }

            $projectConfig = Craft::$app->getProjectConfig();
            foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                $groupPermissions = array_flip($group['permissions'] ?? []);
                $changed = false;
                foreach ($groupPermissions as $permission => $i) {
                    if (str_starts_with($permission, $existingPermissionName)) {
                        // get section uid
                        $sectionUid = str_replace($existingPermissionName, '', $permission);
                        $section = Craft::$app->getEntries()->getSectionByUid($sectionUid);
                        if ($section && $section->propagationMethod == PropagationMethod::Custom) {
                            $groupPermissions[$newPermissionName . ':' . $sectionUid] = true;
                            $changed = true;
                        }
                    }
                }
                if ($changed) {
                    $projectConfig->set("users.groups.$uid.permissions", array_keys($groupPermissions));
                }
            }
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $existingPermissions = array_keys($this->allPermissionNames());
        foreach ($existingPermissions as $existingPermission) {
            $newPermissionName = $this->newPermissionName($existingPermission) . ':';
            $permissionIds = (new Query())
                ->select('id')
                ->from(Table::USERPERMISSIONS)
                ->where(['like', 'name', $newPermissionName])
                ->column($this->db);

            if ($permissionIds) {
                $this->delete(Table::USERPERMISSIONS_USERS, [
                    'permissionId' => $permissionIds,
                ]);
                $this->delete(Table::USERPERMISSIONS, [
                    'id' => $permissionIds,
                ]);
            }

            $projectConfig = Craft::$app->getProjectConfig();
            foreach ($projectConfig->get('users.groups') ?? [] as $uid => $group) {
                $groupPermissions = array_flip($group['permissions'] ?? []);

                $changed = false;
                foreach ($groupPermissions as $permission => $i) {
                    if (str_starts_with($permission, $newPermissionName)) {
                        // get section uid
                        $sectionUid = str_replace($newPermissionName, '', $permission);
                        unset($groupPermissions[$newPermissionName . $sectionUid]);
                        $changed = true;
                    }
                }
                if ($changed) {
                    $projectConfig->set("users.groups.$uid.permissions", array_keys($groupPermissions));
                }
            }
        }

        return true;
    }

    private function allPermissionNames(): array
    {
        return [
            'deleteEntries' => 'deleteEntriesForSite',
            'deletePeerEntries' => 'deletePeerEntriesForSite',
        ];
    }

    private function newPermissionName($existingPermission): string
    {
        $permissions = $this->allPermissionNames();

        return strtolower($permissions[$existingPermission]);
    }
}
