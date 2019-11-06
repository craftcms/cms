<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;

/**
 * m190607_230042_entry_revision_error_tables migration.
 */
class m190607_230042_entry_revision_error_tables extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Drafts
        if (
            $this->db->tableExists(Table::ENTRYDRAFTS) &&
            $this->db->columnExists(Table::ENTRYDRAFTS, 'error') &&
            !$this->db->tableExists('{{%entrydrafterrors}}')
        ) {
            $this->createTable('{{%entrydrafterrors}}', [
                'id' => $this->primaryKey(),
                'draftId' => $this->integer(),
                'error' => $this->text(),
            ]);
            $this->addForeignKey(null, '{{%entrydrafterrors}}', ['draftId'], Table::ENTRYDRAFTS, ['id'], 'CASCADE');

            $query = (new Query())
                ->select(['id', 'error'])
                ->from([Table::ENTRYDRAFTS])
                ->where(['not', ['error' => null]]);

            foreach ($query->each() as $row) {
                $this->insert('{{%entrydrafterrors}}', [
                    'draftId' => $row['id'],
                    'error' => $row['error'],
                ], false);
            }

            $this->dropColumn(Table::ENTRYDRAFTS, 'error');
        }

        // Versions
        if (
            $this->db->tableExists(Table::ENTRYVERSIONS) &&
            $this->db->columnExists(Table::ENTRYVERSIONS, 'error') &&
            !$this->db->tableExists('{{%entryversionerrors}}')
        ) {
            $this->createTable('{{%entryversionerrors}}', [
                'id' => $this->primaryKey(),
                'versionId' => $this->integer(),
                'error' => $this->text(),
            ]);
            $this->addForeignKey(null, '{{%entryversionerrors}}', ['versionId'], Table::ENTRYVERSIONS, ['id'], 'CASCADE');

            $query = (new Query())
                ->select(['id', 'error'])
                ->from([Table::ENTRYVERSIONS])
                ->where(['not', ['error' => null]]);

            foreach ($query->each() as $row) {
                $this->insert('{{%entryversionerrors}}', [
                    'versionId' => $row['id'],
                    'error' => $row['error'],
                ], false);
            }

            $this->dropColumn(Table::ENTRYVERSIONS, 'error');
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190607_230042_entry_revision_error_tables cannot be reverted.\n";
        return false;
    }
}
