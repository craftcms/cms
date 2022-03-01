<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use yii\db\Expression;

/**
 * m220222_122159_full_names migration.
 */
class m220222_122159_full_names extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::USERS, 'fullName', $this->string()->after('username'));

        $this->update(Table::USERS, [
            'fullName' => new Expression("TRIM(CONCAT([[firstName]], ' ', [[lastName]]))"),
        ], new Expression("TRIM(CONCAT([[firstName]], [[lastName]])) <> ''"), updateTimestamp: false);

        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220222_122159_full_names cannot be reverted.\n";
        return false;
    }
}
