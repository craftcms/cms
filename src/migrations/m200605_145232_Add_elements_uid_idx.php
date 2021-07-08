
<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;
use craft\helpers\MigrationHelper;
use craft\db\Table;

/**
 * m200605_145232_Add_elements_uid_idx migration.
 */
class m200605_145232_Add_elements_uid_idx extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!MigrationHelper::doesIndexExist(Table::ELEMENTS, 'uid', false)) {
            $this->createIndex(null, Table::ELEMENTS, 'uid', false);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropIndexIfExists(Table::ELEMENTS, 'uid', false);
    }
}
