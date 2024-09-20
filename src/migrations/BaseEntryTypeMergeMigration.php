<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\Db;
use craft\helpers\Json;
use craft\records\EntryType as EntryTypeRecord;

/**
 * Base entry type merge migration class.
 *
 * This is extended by content migrations generated by the `entry-types/merge` command.
 *
 * @since 5.3.0
 */
class BaseEntryTypeMergeMigration extends Migration
{
    public string $persistingEntryTypeUid;
    public string $outgoingEntryTypeUid;
    /** @var array<string,string> */
    public array $layoutElementUidMap;

    public function safeUp(): bool
    {
        /** @var EntryTypeRecord|null $persistingEntryTypeRecord */
        $persistingEntryTypeRecord = EntryTypeRecord::findWithTrashed()
            ->where(['uid' => $this->persistingEntryTypeUid])
            ->one();
        if (!$persistingEntryTypeRecord) {
            echo "Couldn't find persisting entry type record ($this->persistingEntryTypeUid)";
            return false;
        }

        /** @var EntryTypeRecord|null $outgoingEntryTypeRecord */
        $outgoingEntryTypeRecord = EntryTypeRecord::findWithTrashed()
            ->where(['uid' => $this->outgoingEntryTypeUid])
            ->one();
        if (!$outgoingEntryTypeRecord) {
            echo "Couldn't find outgoing entry type record ($this->outgoingEntryTypeUid)";
            return false;
        }

        $query = (new Query())
            ->select(['es.id', 'es.content'])
            ->from(['es' => Table::ELEMENTS_SITES])
            ->innerJoin(['e' => Table::ENTRIES], '[[e.id]] = [[es.elementId]]')
            ->where(['e.typeId' => $outgoingEntryTypeRecord->id])
            ->andWhere(['not', ['es.content' => null]]);

        $total = (string)$query->count();
        $totalLen = strlen($total);
        $i = 0;
        $rawTableName = $this->db->getSchema()->getRawTableName(Table::ELEMENTS_SITES);

        foreach (Db::each($query) as $row) {
            $i++;
            echo sprintf(
                '    > [%s/%s] Updating %s#%s … ',
                str_pad((string)$i, $totalLen, '0', STR_PAD_LEFT),
                $total,
                $rawTableName,
                $row['id'],
            );

            $content = Json::decode($row['content']);
            $changed = false;
            foreach ($this->layoutElementUidMap as $oldUid => $newUid) {
                if (array_key_exists($oldUid, $content)) {
                    $content[$newUid] = ArrayHelper::remove($content, $oldUid);
                    $changed = true;
                }
            }

            if ($changed) {
                Db::update(
                    Table::ELEMENTS_SITES,
                    ['content' => $content],
                    ['id' => $row['id']],
                );
            }

            echo "✓\n";
        }

        echo '    > Restoring entries … ';
        $elementsTable = Table::ELEMENTS;
        $entriesTable = Table::ENTRIES;
        if ($this->db->getIsMysql()) {
            $this->db->createCommand(<<<SQL
UPDATE $elementsTable [[elements]]
INNER JOIN $entriesTable [[entries]] ON [[entries.id]] = [[elements.id]]
SET [[elements.dateDeleted]] = NULL
WHERE [[entries.typeId]] = $outgoingEntryTypeRecord->id
AND [[entries.deletedWithEntryType]] = 1
SQL)->execute();
        } else {
            $this->db->createCommand(<<<SQL
UPDATE $elementsTable [[elements]]
SET [[dateDeleted]] = NULL
FROM $entriesTable [[entries]]
WHERE [[entries.id]] = [[elements.id]]
AND [[entries.typeId]] = $outgoingEntryTypeRecord->id
AND [[entries.deletedWithEntryType]] = TRUE
SQL)->execute();
        }
        echo "✓\n";

        echo '    > Reassigning entries … ';
        Db::update(
            Table::ENTRIES,
            [
                'typeId' => $persistingEntryTypeRecord->id,
                'deletedWithEntryType' => false,
            ],
            ['typeId' => $outgoingEntryTypeRecord->id],
        );
        echo "✓\n";

        return true;
    }
}
