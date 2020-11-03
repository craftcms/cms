<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m200630_183000_drop_configmap migration.
 */
class m200630_183000_drop_configmap extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn(Table::INFO, 'configMap');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200630_183000_drop_configmap cannot be reverted.\n";
        return false;
    }
}
