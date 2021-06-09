<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m210329_214847_field_column_suffixes migration.
 */
class m210329_214847_field_column_suffixes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::FIELDS, 'columnSuffix', $this->char(8)->after('context'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210329_214847_field_column_suffixes cannot be reverted.\n";
        return false;
    }
}
