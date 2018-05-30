<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m150403_184533_field_version migration.
 */
class m150403_184533_field_version extends Migration
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->columnExists('{{%info}}', 'fieldVersion')) {
            $this->addColumn('{{%info}}', 'fieldVersion', $this->integer()->after('maintenance')->notNull()->defaultValue(1));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m150403_184533_field_version cannot be reverted.\n";

        return false;
    }
}
