<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use yii\db\Expression;

/**
 * m190112_131225_fix_field_layouts migration.
 */
class m190112_131225_fix_field_layouts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get all the duplicate field layout UIDs
        $uids = (new Query())
            ->select(['uid'])
            ->from('{{%fieldlayouts}}')
            ->groupBy(['uid'])
            ->having('count(*) > 1')
            ->column();

        foreach ($uids as $uid) {
            // Get all the IDs
            $ids = (new Query())
                ->select(['id'])
                ->from('{{%fieldlayouts}}')
                ->where(['uid' => $uid])
                ->orderBy(new Expression('[[dateDeleted]] is null desc, [[id]] desc'))
                ->column();

            $targetId = array_shift($ids);

            // Update the elements
            $this->update('{{%elements}}', [
                'fieldLayoutId' => $targetId,
            ], ['fieldLayoutId' => $ids], [], false);

            // Delete the old layouts
            $this->delete('{{%fieldlayouts}}', ['id' => $ids]);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190112_131225_fix_field_layouts cannot be reverted.\n";
        return false;
    }
}
