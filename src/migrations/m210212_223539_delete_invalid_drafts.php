<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m210212_223539_delete_invalid_drafts migration.
 */
class m210212_223539_delete_invalid_drafts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Soft-delete any unpublished drafts that should have been soft-deleted along with their entry types
        $elementsTable = Table::ELEMENTS;
        $entriesTable = Table::ENTRIES;
        $entryTypesTable = Table::ENTRYTYPES;

        if ($this->db->getIsMysql()) {
            $sql1 = <<<SQL
UPDATE $entriesTable [[en]]
INNER JOIN $elementsTable [[el]] ON [[el.id]] = [[en.id]]
INNER JOIN $entryTypesTable [[et]] ON [[et.id]] = [[en.typeId]]
SET [[en.deletedWithEntryType]] = 1
WHERE [[et.dateDeleted]] IS NOT NULL
AND [[el.dateDeleted]] IS NULL
SQL;
            $sql2 = <<<SQL
UPDATE $elementsTable [[el]]
INNER JOIN $entriesTable [[en]] ON [[en.id]] = [[el.id]]
INNER JOIN $entryTypesTable [[et]] ON [[et.id]] = [[en.typeId]]
SET [[el.dateDeleted]] = [[et.dateDeleted]]
WHERE [[et.dateDeleted]] IS NOT NULL
AND [[el.dateDeleted]] IS NULL
SQL;
        } else {
            $sql1 = <<<SQL
UPDATE $entriesTable [[en1]]
SET [[deletedWithEntryType]] = true
FROM $entriesTable [[en]]
INNER JOIN $elementsTable [[el]] ON [[el.id]] = [[en.id]]
INNER JOIN $entryTypesTable [[et]] ON [[et.id]] = [[en.typeId]]
WHERE [[en1.id]] = [[en.id]]
AND [[et.dateDeleted]] IS NOT NULL
AND [[el.dateDeleted]] IS NULL
SQL;
            $sql2 = <<<SQL
UPDATE $elementsTable [[el]]
SET [[dateDeleted]] = [[et.dateDeleted]]
FROM $entriesTable [[en]]
INNER JOIN $entryTypesTable [[et]] ON [[et.id]] = [[en.typeId]]
WHERE [[en.id]] = [[el.id]]
AND [[et.dateDeleted]] IS NOT NULL
AND [[el.dateDeleted]] IS NULL
SQL;
        }

        $this->execute($sql1);
        $this->execute($sql2);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210212_223539_soft_delete_entries cannot be reverted.\n";
        return false;
    }
}
