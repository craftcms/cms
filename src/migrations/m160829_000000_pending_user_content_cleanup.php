<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\elements\Entry;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160829_000000_pending_user_content_cleanup extends Migration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return bool
     */
    public function safeUp(): bool
    {
        // Find any orphaned entries.
        $ids = (new Query())
            ->select(['el.id'])
            ->from(['{{%elements}} el'])
            ->leftJoin('{{%entries}} en', '[[en.id]] = [[el.id]]')
            ->where([
                'el.type' => Entry::class,
                'en.id' => null
            ])
            ->column($this->db);

        if (!empty($ids)) {
            echo '    > Found '.count($ids).' orphaned element IDs in the elements table: '.implode(', ', $ids)."\n";

            // Delete 'em
            $this->delete('{{%elements}}', ['id' => $ids]);
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160829_000000_pending_user_content_cleanup cannot be reverted.\n";

        return false;
    }
}
