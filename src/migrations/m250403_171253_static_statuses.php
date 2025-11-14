<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;

/**
 * m250403_171253_static_statuses migration.
 */
class m250403_171253_static_statuses extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::ENTRIES, 'status', $this->enum('status', [
            Entry::STATUS_LIVE,
            Entry::STATUS_PENDING,
            Entry::STATUS_EXPIRED,
        ])->notNull()->defaultValue(Entry::STATUS_LIVE)->after('expiryDate'));
        $this->createIndex(null, Table::ENTRIES, ['status'], false);

        $currentTimeDb = Db::prepareDateForDb(DateTimeHelper::now());
        $this->update(Table::ENTRIES, ['status' => Entry::STATUS_PENDING], [
            'or',
            ['postDate' => null],
            ['>', 'postDate', $currentTimeDb],
        ]);
        $this->update(Table::ENTRIES, ['status' => Entry::STATUS_EXPIRED], [
            'and',
            ['not', ['postDate' => null]],
            ['<=', 'expiryDate', $currentTimeDb],
        ]);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        $this->dropColumn(Table::ENTRIES, 'status');
        return true;
    }
}
