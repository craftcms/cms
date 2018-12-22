<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170206_142126_system_name migration.
 */
class m170206_142126_system_name extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if ($this->db->columnExists('{{%info}}', 'name')) {
            return true;
        }

        $systemName = Craft::$app->getSites()->getPrimarySite()->name;

        $this->addColumn('{{%info}}', 'name', $this->string()->after('timezone'));
        $this->update('{{%info}}', ['name' => $systemName]);
        $this->alterColumn('{{%info}}', 'name', $this->string()->notNull());

        Craft::$app->getInfo()->name = $systemName;

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170206_142126_system_name cannot be reverted.\n";

        return false;
    }
}
