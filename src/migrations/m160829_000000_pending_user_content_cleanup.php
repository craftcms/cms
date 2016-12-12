<?php
namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;

/**
 * The class name is the UTC timestamp in the format of mYYMMDD_HHMMSS_migrationName
 */
class m160829_000000_pending_user_content_cleanup extends Migration
{
    /**
     * Any migration code in here is wrapped inside of a transaction.
     *
     * @return boolean
     */
    public function safeUp()
    {
        // Find any orphaned entries.
        $ids = (new Query())
            ->select(['el.id'])
            ->from(['{{%elements}} el'])
            ->leftJoin('{{%entries}} en', '[[en.id]] = [[el.id]]')
            ->where([
                'el.type' => 'craft\elements\Entry',
                'en.id' => null
            ])
            ->column();

        if ($ids) {
            Craft::info('Found '.count($ids).' orphaned element IDs in the elements table: '.implode(', ', $ids));

            // Delete 'em
            $this->delete('{{%elements}}', ['id' => $ids]);

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
