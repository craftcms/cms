<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m200619_212955_title_instructions migration.
 */
class m200619_212955_title_instructions extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::ENTRYTYPES, 'titleInstructions', $this->text()->after('titleLabel'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200619_212955_title_instructions cannot be reverted.\n";
        return false;
    }
}
