<?php

namespace craft\app\migrations;

use craft\app\db\Migration;
use craft\app\db\Query;

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
            ->select('id, name')
            ->from('{{%userpermissions}}')
            ->where(['like', 'name', '%volume%', false])
            ->all();

        foreach ($permissions as $permission) {
            $newName = str_replace('uploadtovolume', 'saveassetinvolume', $permission['name']);
            $newName = str_replace('createsubfoldersinvolume', 'createfoldersinvolume', $newName);
            $newName = str_replace('removefromvolume', 'deletefilesandfoldersinvolume', $newName);
            $this->update('{{%userpermissions}}', ['name' => $newName],
                ['id' => $permission['id']], [], false);
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
