<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;

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
        $this->addColumn('{{%sites}}', 'primary', $this->boolean()->after('groupId')->defaultValue(false)->notNull());

        $primarySiteId = (new Query())
            ->select(['id'])
            ->from(['{{%sites}}'])
            ->orderBy(['sortOrder' => SORT_ASC])
            ->scalar();

        $this->update('{{%sites}}', ['primary' => true], ['id' => $primarySiteId]);
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
