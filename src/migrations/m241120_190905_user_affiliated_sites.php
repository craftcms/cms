<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m241120_190905_user_affiliated_sites migration.
 */
class m241120_190905_user_affiliated_sites extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::USERS, 'affiliatedSiteId', $this->integer()->after('photoId'));
        $this->addForeignKey(null, Table::USERS, ['affiliatedSiteId'], Table::SITES, ['id'], 'SET NULL', null);
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropForeignKeyIfExists(Table::USERS, 'affiliatedSiteId');
        $this->dropColumn(Table::USERS, 'affiliatedSiteId');
        return true;
    }
}
