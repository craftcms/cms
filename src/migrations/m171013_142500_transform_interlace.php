<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\FileHelper;
use craft\helpers\MigrationHelper;

/**
 * m171013_142500_transform_interlace migration.
 */
class m171013_142500_transform_interlace extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%assettransforms}}', 'interlace', $this->enum('interlace', ['none', 'line', 'plane', 'partition'])->notNull()->defaultValue('none'));
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171013_142500_transform_interlace cannot be reverted.\n";
        return false;
    }
}
