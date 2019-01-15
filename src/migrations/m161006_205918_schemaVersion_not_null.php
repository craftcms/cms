<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m161006_205918_schemaVersion_not_null migration.
 */
class m161006_205918_schemaVersion_not_null extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update(
            Table::PLUGINS,
            ['schemaVersion' => '1.0.0'],
            ['schemaVersion' => null],
            [],
            false);

        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute('alter table {{%plugins}} alter column [[schemaVersion]] type varchar(15), alter column [[schemaVersion]] set not null');
        } else {
            $this->alterColumn(Table::PLUGINS, 'schemaVersion', $this->string(15)->notNull());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161006_205918_schemaVersion_not_null cannot be reverted.\n";

        return false;
    }
}
