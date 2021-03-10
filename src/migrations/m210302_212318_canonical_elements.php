<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;

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
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m210302_212318_canonical_elements cannot be reverted.\n";
        return false;
    }
}
