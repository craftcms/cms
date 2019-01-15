<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m180328_115523_new_license_key_statuses migration.
 */
class m180328_115523_new_license_key_statuses extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $values = ['valid', 'invalid', 'mismatched', 'astray', 'unknown'];
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            $check = '[[licenseKeyStatus]] in (';
            foreach ($values as $i => $value) {
                if ($i != 0) {
                    $check .= ',';
                }
                $check .= $this->db->quoteValue($value);
            }
            $check .= ')';
            $this->execute("alter table {{%plugins}} drop constraint {{%plugins_licenseKeyStatus_check}}, add check ({$check})");
        } else {
            $this->alterColumn(Table::PLUGINS, 'licenseKeyStatus', $this->enum('licenseKeyStatus', $values)->notNull()->defaultValue('unknown'));
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180328_115523_new_license_key_statuses cannot be reverted.\n";
        return false;
    }
}
