<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m170206_142126_system_name migration.
 */
class m170206_142126_system_name extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists(Table::INFO, 'name')) {
            return true;
        }

        $systemName = Craft::$app->getSites()->getPrimarySite()->getName();

        $this->addColumn(Table::INFO, 'name', $this->string()->after('timezone'));
        $this->update(Table::INFO, ['name' => $systemName]);
        $this->alterColumn(Table::INFO, 'name', $this->string()->notNull());

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170206_142126_system_name cannot be reverted.\n";

        return false;
    }
}
