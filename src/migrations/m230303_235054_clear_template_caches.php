<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m230303_235054_clear_template_caches migration.
 */
class m230303_235054_clear_template_caches extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        // justa placeholder migration to ensure template caches get cleared at this point
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        return true;
    }
}
