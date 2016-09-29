<?php
namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\elements\Entry;

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
    public function safeUp()
    {
        // Find any orphaned entries.
        $ids = (new Query())
            ->select('el.id')
            ->from('{{%elements}} el')
            ->leftJoin('{{%entries}} en', 'en.id = el.id')
            ->where(
                ['and', 'el.type = :type', 'en.id is null'],
                [':type' => Entry::class]
            )
            ->column();

        if ($ids) {
            Craft::info('Found '.count($ids).' orphaned element IDs in the elements table: '.implode(', ', $ids));

            // Delete 'em
            $this->delete('{{%elements}}', ['in', 'id', $ids]);

            Craft::info('They have been murdered.');
        } else {
            Craft::info('All good here.');
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo 'm160829_000000_pending_user_content_cleanup cannot be reverted.\n';
        return false;
    }
}
