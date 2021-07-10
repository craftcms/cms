<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m210624_222934_drop_deprecated_tables migration.
 */
class m210624_222934_drop_deprecated_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropTableIfExists('{{%entrydrafterrors}}');
        $this->dropTableIfExists('{{%entryversionerrors}}');
        $this->dropTableIfExists('{{%entrydrafts}}');
        $this->dropTableIfExists('{{%entryversions}}');
        $this->dropTableIfExists('{{%projectconfignames}}');
        $this->dropTableIfExists('{{%templatecacheelements}}');
        $this->dropTableIfExists('{{%templatecachequeries}}');
        $this->dropTableIfExists('{{%templatecachecriteria}}');
        $this->dropTableIfExists('{{%templatecaches}}');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210624_222934_drop_deprecated_tables cannot be reverted.\n";
        return false;
    }
}
