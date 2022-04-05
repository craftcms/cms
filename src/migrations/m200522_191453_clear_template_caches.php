<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m200522_191453_clear_template_caches migration.
 */
class m200522_191453_clear_template_caches extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->delete(Table::TEMPLATECACHES);
    }
}
