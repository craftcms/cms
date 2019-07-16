<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m171218_143135_longtext_query_column migration.
 */
class m171218_143135_longtext_query_column extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->getIsMysql()) {
            $this->alterColumn(Table::TEMPLATECACHEQUERIES, 'query', $this->longText()->notNull());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171218_143135_longtext_query_column cannot be reverted.\n";
        return false;
    }
}
