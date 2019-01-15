<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

/**
 * m180404_182320_edition_changes migration.
 */
class m180404_182320_edition_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update(Table::INFO, ['edition' => 1], ['edition' => 2]);

        if ($this->db->columnExists(Table::USERS, 'client')) {
            $this->dropColumn(Table::USERS, 'client');
        }

        Craft::$app->getCache()->delete('licensedEdition');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180404_182320_edition_changes cannot be reverted.\n";
        return false;
    }
}
