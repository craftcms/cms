<?php

namespace craft\migrations;

use Craft;
use craft\config\DbConfig;
use craft\db\Migration;
use craft\db\Table;

/**
 * m161220_000000_volumes_hasurl_notnull migration.
 */
class m161220_000000_volumes_hasurl_notnull extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // https://github.com/yiisoft/yii2/issues/4492
        if (Craft::$app->getDb()->getDriverName() === DbConfig::DRIVER_PGSQL) {
            $this->alterColumn(Table::VOLUMES, 'hasUrls', 'SET NOT NULL');
            $this->alterColumn(Table::VOLUMES, 'hasUrls', 'SET DEFAULT FALSE');
        } else {
            $this->alterColumn(Table::VOLUMES, 'hasUrls', $this->boolean()->defaultValue(false)->notNull());
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161220_000000_volumes_hasurl_notnull cannot be reverted.\n";

        return false;
    }
}
