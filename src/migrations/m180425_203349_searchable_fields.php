<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180425_203349_searchable_fields migration.
 */
class m180425_203349_searchable_fields extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::FIELDS, 'searchable', $this->boolean()->notNull()->defaultValue(true)->after('instructions'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180425_203349_searchable_fields cannot be reverted.\n";
        return false;
    }
}
