<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m170821_180624_deprecation_line_nullable migration.
 */
class m170821_180624_deprecation_line_nullable extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->alterColumn('{{%deprecationerrors}}', 'line', $this->smallInteger()->unsigned());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170821_180624_deprecation_line_nullable cannot be reverted.\n";
        return false;
    }
}
