<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\StringHelper;

/**
 * m200825_051217_project_config_version migration.
 */
class m200825_051217_project_config_version extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::INFO, 'configVersion', $this->char(12)->notNull()->defaultValue('000000000000')->after('maintenance'));
        $this->update(Table::INFO, [
            'configVersion' => StringHelper::randomString(12),
        ], '', [], false);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200825_051217_project_config_version cannot be reverted.\n";
        return false;
    }
}
