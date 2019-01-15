<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;

/**
 * m161021_102916_fix_recent_entries_widgets migration.
 */
class m161021_102916_fix_recent_entries_widgets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Get a mapping of site handles to IDs
        // (this is the closest thing to the original locale IDs we have now)
        $siteResults = (new Query())
            ->select(['id', 'handle'])
            ->from([Table::SITES])
            ->all($this->db);
        $siteIdsByHandle = ArrayHelper::map($siteResults, 'handle', 'id');

        $sitesMigration = new m160807_144858_sites();
        $sitesMigration->updateRecentEntriesWidgets($siteIdsByHandle);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161021_102916_fix_recent_entries_widgets cannot be reverted.\n";

        return false;
    }
}
