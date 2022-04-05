<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m150519_150900_fieldversion_conversion migration.
 */
class m150519_150900_fieldversion_conversion extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn(Table::INFO, 'fieldVersion', 'char(12)');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150519_150900_fieldversion_conversion cannot be reverted.\n";

        return false;
    }
}
