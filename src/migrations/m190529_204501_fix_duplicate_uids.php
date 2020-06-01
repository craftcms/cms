<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\StringHelper;

/**
 * m190529_204501_fix_duplicate_uids migration.
 */
class m190529_204501_fix_duplicate_uids extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // No need to run this if we're updating from < 3.2.0-alpha.6
        if (version_compare(Craft::$app->getInfo()->version, '3.2.0-alpha.6', '<')) {
            return;
        }

        $uids = [];
        $query = (new Query())
            ->select(['id', 'uid'])
            ->from([Table::ELEMENTS])
            ->where([
                'in', 'uid', (new Query())
                    ->select(['uid'])
                    ->from([Table::ELEMENTS])
                    ->groupBy(['uid'])
                    ->having('count([[uid]]) > 1')
            ])
            ->orderBy(['id' => SORT_ASC]);

        foreach ($query->each() as $result) {
            if (!isset($uids[$result['uid']])) {
                // This is the first time this UID was issued
                $uids[$result['uid']] = true;
                continue;
            }

            // Duplicate! Give this element a unique UID
            $this->update(Table::ELEMENTS, ['uid' => StringHelper::UUID()], ['id' => $result['id']], [], false);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190529_204501_fix_duplicate_uids cannot be reverted.\n";
        return false;
    }
}
