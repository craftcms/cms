<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m170703_181539_plugins_table_tweaks migration.
 */
class m170703_181539_plugins_table_tweaks extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Remove lengths
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute('alter table {{%plugins}} alter column [[handle]] type varchar(255), alter column [[handle]] set not null');
            $this->execute('alter table {{%plugins}} alter column [[version]] type varchar(255), alter column [[version]] set not null');
            $this->execute('alter table {{%plugins}} alter column [[schemaVersion]] type varchar(255), alter column [[schemaVersion]] set not null');
        } else {
            $this->alterColumn(Table::PLUGINS, 'handle', $this->string()->notNull());
            $this->alterColumn(Table::PLUGINS, 'version', $this->string()->notNull());
            $this->alterColumn(Table::PLUGINS, 'schemaVersion', $this->string()->notNull());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170703_181539_plugins_table_tweaks cannot be reverted.\n";
        return false;
    }
}
