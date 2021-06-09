<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;
use craft\helpers\MigrationHelper;

/**
 * m210302_212318_canonical_elements migration.
 */
class m210302_212318_canonical_elements extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::ELEMENTS, 'canonicalId', $this->integer()->after('id'));
        $this->addColumn(Table::ELEMENTS, 'dateLastMerged', $this->dateTime()->after('dateUpdated'));

        $this->addForeignKey(null, Table::ELEMENTS, ['canonicalId'], Table::ELEMENTS, ['id'], 'SET NULL');

        // Set the canonicalId for all drafts
        $elementsTable = Table::ELEMENTS;

        foreach ([Table::DRAFTS => 'draftId', Table::REVISIONS => 'revisionId'] as $table => $fk) {
            if ($this->db->getIsMysql()) {
                $sql = <<<SQL
UPDATE $elementsTable [[e]]
INNER JOIN $table [[d]] ON [[d.id]] = [[e.$fk]]
SET [[e.canonicalId]] = [[d.sourceId]]
SQL;
            } else {
                $sql = <<<SQL
UPDATE $elementsTable [[e]]
SET [[canonicalId]] = [[d.sourceId]]
FROM $table [[d]]
WHERE [[d.id]] = [[e.$fk]]
SQL;
            }

            $this->execute($sql);
        }

        // Set the dateLastMerged for all drafts
        $draftsTable = Table::DRAFTS;

        if ($this->db->getIsMysql()) {
            $sql = <<<SQL
UPDATE $elementsTable [[e]]
INNER JOIN $draftsTable [[d]] ON [[d.id]] = [[e.$fk]]
SET [[e.dateLastMerged]] = [[d.dateLastMerged]]
SQL;
        } else {
            $sql = <<<SQL
UPDATE $elementsTable [[e]]
SET [[dateLastMerged]] = [[d.dateLastMerged]]
FROM $draftsTable [[d]]
WHERE [[d.id]] = [[e.$fk]]
SQL;
        }

        $this->execute($sql);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropForeignKey(Table::ELEMENTS, ['canonicalId'], $this);
        $this->dropColumn(Table::ELEMENTS, 'canonicalId');
        return;

        echo "m210302_212318_canonical_elements cannot be reverted.\n";
        return false;
    }
}
