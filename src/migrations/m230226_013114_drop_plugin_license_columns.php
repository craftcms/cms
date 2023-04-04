<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\enums\LicenseKeyStatus;

/**
 * m230226_013114_drop_plugin_license_columns migration.
 */
class m230226_013114_drop_plugin_license_columns extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists(Table::PLUGINS, 'licenseKeyStatus')) {
            if ($this->db->getIsPgsql()) {
                $this->execute(sprintf('alter table %s drop constraint %s', Table::PLUGINS, '{{%plugins_licenseKeyStatus_check}}'));
            }
            $this->dropColumn(Table::PLUGINS, 'licenseKeyStatus');
        }
        if ($this->db->columnExists(Table::PLUGINS, 'licensedEdition')) {
            $this->dropColumn(Table::PLUGINS, 'licensedEdition');
        }

        $cache = Craft::$app->getCache();
        $cache->delete('licensedEdition');
        $cache->delete('licenseKeyStatus');

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        $this->addColumn(Table::PLUGINS, 'licenseKeyStatus', $this->enum('licenseKeyStatus', [
            LicenseKeyStatus::Valid,
            LicenseKeyStatus::Trial,
            LicenseKeyStatus::Invalid,
            LicenseKeyStatus::Mismatched,
            LicenseKeyStatus::Astray,
            LicenseKeyStatus::Unknown,
        ])->notNull()->defaultValue(LicenseKeyStatus::Unknown)->after('schemaVersion'));
        $this->addColumn(Table::PLUGINS, 'licensedEdition', $this->string()->after('licenseKeyStatus'));

        Craft::$app->getCache()->delete('licenseInfo');

        return true;
    }
}
