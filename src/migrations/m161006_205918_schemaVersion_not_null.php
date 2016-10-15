<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;

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
        ], 'schemaVersion is null', [], false);

        $this->alterColumn('{{%plugins}}', 'schemaVersion', $this->string(15)->notNull());
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
