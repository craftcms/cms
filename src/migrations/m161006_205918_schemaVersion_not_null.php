<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\services\Config;

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
        $this->update('{{%plugins}}', [
            'schemaVersion' => '1.0.0'
        ], Craft::$app->getDb()->getSchema()->quoteColumnName('schemaVersion').' IS NULL', [], false);

        $database = Craft::$app->getConfig()->get('driver', 'db');

        // Two statements here because of a Yii 2/PostgreSQL bug:
        // https://github.com/yiisoft/yii2/issues/12077
        if ($database == 'mysql') {
            $this->alterColumn('{{%plugins}}', 'schemaVersion', $this->string(15)->notNull());
        } else if ($database == 'pgsql') {
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
