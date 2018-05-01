<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;

/**
 * m171011_214115_site_groups migration.
 */
class m171011_214115_site_groups extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // In case this was run in a previous update attempt
        $this->dropTableIfExists('{{%sitegroups}}');

        // Make the schema changes
        $this->addColumn('{{%sites}}', 'groupId', $this->integer()->after('id'));
        $this->createTable('{{%sitegroups}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string()->notNull(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);
        $this->createIndex(null, '{{%sitegroups}}', ['name'], true);

        // Create the first site group
        $name = (new Query())
            ->select(['name'])
            ->from('{{%sites}}')
            ->orderBy(['sortOrder' => SORT_ASC])
            ->scalar();

        $this->insert('{{%sitegroups}}', [
            'name' => $name,
        ]);

        $groupId = $this->db->getLastInsertID('{{%sitegroups}}');

        // Assign all the current sites to it
        $this->update('{{%sites}}', ['groupId' => $groupId], '', [], false);

        // Now we can set the groupId column to NOT NULL
        if ($this->db->getIsPgsql()) {
            // Manually construct the SQL for Postgres
            // (see https://github.com/yiisoft/yii2/issues/12077)
            $this->execute('alter table {{%sites}} alter column [[groupId]] set not null');
        } else {
            $this->alterColumn('{{%sites}}', 'groupId', $this->integer()->notNull());
        }

        // Set the foreign key
        $this->addForeignKey(null, '{{%sites}}', ['groupId'], '{{%sitegroups}}', ['id'], 'CASCADE', null);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m171011_214115_site_groups cannot be reverted.\n";
        return false;
    }
}
