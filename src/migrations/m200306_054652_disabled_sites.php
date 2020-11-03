<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m200306_054652_disabled_sites migration.
 */
class m200306_054652_disabled_sites extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::SITES, 'enabled', $this->boolean()->notNull()->defaultValue(true)->after('primary'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->dropColumn(Table::SITES, 'enabled');
    }
}
