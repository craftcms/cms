<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m161013_175052_newParentId migration.
 */
class m161013_175052_newParentId extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->replace(Table::ENTRYDRAFTS, 'data', '"parentId":', '"newParentId":');
        $this->replace(Table::ENTRYVERSIONS, 'data', '"parentId":', '"newParentId":');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161013_175052_newParentId cannot be reverted.\n";

        return false;
    }
}
