<?php

namespace craft\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;

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
            '{{%plugins}}',
            ['schemaVersion' => '1.0.0'],
            ['schemaVersion' => null],
            [],
            false);

        $database = Craft::$app->getConfig()->getDb()->driver;

        // Two statements here because of a Yii 2/PostgreSQL bug:
        // https://github.com/yiisoft/yii2/issues/12077
        if ($database === DbConfig::DRIVER_MYSQL) {
            $this->alterColumn('{{%plugins}}', 'schemaVersion', $this->string(15)->notNull());
        } else if ($database === DbConfig::DRIVER_PGSQL) {
            $pluginTable = $this->db->getSchema()->defaultSchema.'.'.$this->db->getSchema()->getRawTableName('plugins');
            $this->db->createCommand()->setSql('ALTER TABLE '.$pluginTable.' ALTER COLUMN "schemaVersion" TYPE varchar(15), ALTER COLUMN "schemaVersion" SET NOT NULL')->execute();
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
