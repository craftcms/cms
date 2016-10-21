<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\ArrayHelper;
use craft\app\helpers\Json;

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
            ->from('{{%sites}}')
            ->all();
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
