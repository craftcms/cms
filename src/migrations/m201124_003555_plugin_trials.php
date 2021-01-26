<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\enums\LicenseKeyStatus;
use yii\db\Expression;

/**
 * m201124_003555_plugin_trials migration.
 */
class m201124_003555_plugin_trials extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $statuses = [
            LicenseKeyStatus::Valid,
            LicenseKeyStatus::Trial,
            LicenseKeyStatus::Invalid,
            LicenseKeyStatus::Mismatched,
            LicenseKeyStatus::Astray,
            LicenseKeyStatus::Unknown,
        ];

        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            $checkSql = '[[licenseKeyStatus]] in (' .
                implode(',', array_map(function(string $status) {
                    return $this->db->quoteValue($status);
                }, $statuses)) .
                ')';
            $this->execute("alter table {{%plugins}} drop constraint {{%plugins_licenseKeyStatus_check}}, add check ($checkSql)");
        } else {
            $this->alterColumn(Table::PLUGINS, 'licenseKeyStatus',
                $this->enum('licenseKeyStatus', $statuses)->notNull()->defaultValue(LicenseKeyStatus::Unknown));
        }

        // Clear out the existing 'invalid' license key statuses, since they may actually be 'trial' now
        $this->update(Table::PLUGINS, [
            'licenseKeyStatus' => LicenseKeyStatus::Unknown,
        ], [
            'licenseKeyStatus' => LicenseKeyStatus::Invalid,
        ], [], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m201124_003555_plugin_trials cannot be reverted.\n";
        return false;
    }
}
