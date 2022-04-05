<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Table;

/**
 * m210214_202731_track_saved_drafts migration.
 */
class m210214_202731_track_saved_drafts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::DRAFTS, 'saved', $this->boolean()->notNull()->defaultValue(true));
        $this->createIndex(null, Table::DRAFTS, ['saved'], false);

        // Mark all unpublished drafts where dateUpdated == dateCreate as unsaved
        $elementsTable = Table::ELEMENTS;
        $draftsTable = Table::DRAFTS;

        if ($this->db->getIsMysql()) {
            $sql = <<<SQL
UPDATE $draftsTable [[d]]
INNER JOIN $elementsTable [[e]] ON [[e.draftId]] = [[d.id]]
SET [[d.saved]] = 0
WHERE
  [[d.sourceId]] IS NULL AND
  [[e.dateUpdated]] = [[e.dateCreated]]
SQL;
        } else {
            $sql = <<<SQL
UPDATE $draftsTable [[d]]
SET [[saved]] = FALSE
FROM $elementsTable [[e]]
WHERE
  [[e.draftId]] = [[d.id]] AND
  [[d.sourceId]] IS NULL AND
  [[e.dateUpdated]] = [[e.dateCreated]]
SQL;
        }

        $this->execute($sql);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210214_202731_track_saved_drafts cannot be reverted.\n";
        return false;
    }
}
