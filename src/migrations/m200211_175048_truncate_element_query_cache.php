<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m200211_175048_truncate_element_query_cache migration.
 */
class m200211_175048_truncate_element_query_cache extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->truncateTable(Table::TEMPLATECACHEQUERIES);
    }
}
