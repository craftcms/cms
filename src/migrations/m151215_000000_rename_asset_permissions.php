<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m151215_000000_rename_asset_permissions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Update permissions
        $permissions = (new Query())
            ->select(['id', 'name'])
            ->from(['{{%userpermissions}}'])
            ->where(['like', 'name', 'volume'])
            ->all($this->db);

        foreach ($permissions as $permission) {
            $newName = str_replace([
                'uploadtovolume',
                'createsubfoldersinvolume',
                'removefromvolume'
            ], [
                'saveassetinvolume',
                'createfoldersinvolume',
                'deletefilesandfoldersinvolume'
            ], $permission['name']);

            $this->update('{{%userpermissions}}', ['name' => $newName], ['id' => $permission['id']], [], false);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m151215_000000_rename_asset_permissions cannot be reverted.\n";

        return false;
    }
}
