<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m180404_172937_edition_changes migration.
 */
class m180404_172937_edition_changes extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->update('{{%info}}', ['edition' => 1], ['edition' => 2]);

        if ($this->db->columnExists('{{%users}}', 'client')) {
            $this->dropColumn('{{%users}}', 'client');
        }

        Craft::$app->getCache()->delete('licensedEdition');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m180404_172937_edition_changes cannot be reverted.\n";
        return false;
    }
}
