<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

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
        // Two statements here because of a Yii 2/PostgreSQL bug:
        // https://github.com/yiisoft/yii2/issues/12077
        if ($this->db->getIsMysql()) {
            $this->alterColumn('{{%plugins}}', 'handle', $this->string()->notNull());
            $this->alterColumn('{{%plugins}}', 'version', $this->string()->notNull());
            $this->alterColumn('{{%plugins}}', 'schemaVersion', $this->string()->notNull());
        } else {
            $pluginTable = $this->db->getSchema()->defaultSchema.'.'.$this->db->getSchema()->getRawTableName('plugins');

            $this->db->createCommand()->setSql('ALTER TABLE '.$pluginTable.' ALTER COLUMN "handle" TYPE varchar(255), ALTER COLUMN "handle" SET NOT NULL')->execute();
            $this->db->createCommand()->setSql('ALTER TABLE '.$pluginTable.' ALTER COLUMN "version" TYPE varchar(255), ALTER COLUMN "version" SET NOT NULL')->execute();
            $this->db->createCommand()->setSql('ALTER TABLE '.$pluginTable.' ALTER COLUMN "schemaVersion" TYPE varchar(255), ALTER COLUMN "schemaVersion" SET NOT NULL')->execute();
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
