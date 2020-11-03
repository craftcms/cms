<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m200127_172522_queue_channels migration.
 */
class m200127_172522_queue_channels extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropIndexIfExists(Table::QUEUE, ['fail', 'timeUpdated', 'timePushed'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::QUEUE, ['fail', 'timeUpdated', 'delay'], false, $this);

        $this->addColumn(Table::QUEUE, 'channel', $this->string()->notNull()->defaultValue('queue')->after('id'));

        $this->createIndex(null, Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'timePushed']);
        $this->createIndex(null, Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'delay']);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropIndexIfExists(Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'timePushed'], false, $this);
        MigrationHelper::dropIndexIfExists(Table::QUEUE, ['channel', 'fail', 'timeUpdated', 'delay'], false, $this);

        $this->dropColumn(Table::QUEUE, 'channel');

        $this->createIndex(null, Table::QUEUE, ['fail', 'timeUpdated', 'timePushed']);
        $this->createIndex(null, Table::QUEUE, ['fail', 'timeUpdated', 'delay']);
    }
}
