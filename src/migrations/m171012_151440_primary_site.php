<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m171012_151440_primary_site migration.
 */
class m171012_151440_primary_site extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::SITES, 'primary', $this->boolean()->after('groupId')->defaultValue(false)->notNull());

        $primarySiteId = (new Query())
            ->select(['id'])
            ->from([Table::SITES])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->scalar();

        $this->update(Table::SITES, ['primary' => true], ['id' => $primarySiteId]);

        // Refresh all sites
        // ---------------------------------------------------------------------

        Craft::$app->getSites()->refreshSites();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171012_151440_primary_site cannot be reverted.\n";
        return false;
    }
}
