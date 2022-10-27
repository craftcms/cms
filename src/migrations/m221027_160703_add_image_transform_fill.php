<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m221027_160703_add_image_transform_fill migration.
 */
class m221027_160703_add_image_transform_fill extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // Place migration code here...
        $this->addColumn(Table::IMAGETRANSFORMS, 'fill', $this->string(11)->null()->after('interlace'));

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m221027_160703_add_image_transform_fill cannot be reverted.\n";
        return false;
    }
}
