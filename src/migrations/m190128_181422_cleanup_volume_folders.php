<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m190128_181422_cleanup_volume_folders migration.
 */
class m190128_181422_cleanup_volume_folders extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $folderRows = (new Query())
            ->select(['id', 'parentId'])
            ->from(['{{%volumefolders}}'])
            ->where(['path' => './'])
            ->pairs();

        if (!empty($folderRows)) {
            foreach ($folderRows as $folderId => $parentId) {
                $this->update('{{%assets}}', ['folderId' => $parentId], ['folderId' => $folderId]);
                $this->delete('{{%volumefolders}}', ['id' => $folderId]);
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190128_181422_cleanup_volume_folders cannot be reverted.\n";
        return false;
    }
}
