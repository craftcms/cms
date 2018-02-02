<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m170126_000000_assets_focal_point migration.
 */
class m170126_000000_assets_focal_point extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%assets}}', 'focalPoint', $this->string(20)->after('size')->null());
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170126_000000_assets_focal_point cannot be reverted.\n";

        return false;
    }
}
