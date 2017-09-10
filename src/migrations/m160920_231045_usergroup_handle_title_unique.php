<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\helpers\MigrationHelper;

/**
 * m160920_231045_usergroup_handle_title_unique migration.
 */
class m160920_231045_usergroup_handle_title_unique extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->_handleDupes('handle');
        $this->_handleDupes('name');

        if (!MigrationHelper::doesIndexExist('{{%usergroups}}', 'handle', true)) {
            $this->createIndex(null, '{{%usergroups}}', ['handle'], true);
        }

        if (!MigrationHelper::doesIndexExist('{{%usergroups}}', 'name', true)) {
            $this->createIndex(null, '{{%usergroups}}', ['name'], true);
        }
    }

    /**
     * @param string $type Either 'handle' or 'name'
     */
    private function _handleDupes(string $type)
    {
        echo '    > looking for duplicate user group '.$type.'s ...';
        // Get any duplicates.
        $duplicates = (new Query())
            ->select($type)
            ->from(['{{%usergroups}}'])
            ->groupBy([$type])
            ->having('count('.$this->db->quoteValue($type).') > '.$this->db->quoteValue('1'))
            ->all($this->db);

        if (!empty($duplicates)) {
            echo ' found '.count($duplicates)."\n";

            foreach ($duplicates as $duplicate) {
                echo '    > fixing duplicate "'.$duplicate[$type].'" user group '.$type."s\n";

                $rows = (new Query())
                    ->from(['{{%usergroups}}'])
                    ->where([$type => $duplicate[$type]])
                    ->orderBy(['dateCreated' => SORT_ASC])
                    ->all($this->db);

                // Find more than one?
                if (count($rows) > 1) {
                    // Skip the first (the earliest created), since presumably it's the good one.
                    unset($rows[0]);

                    foreach ($rows as $row) {
                        $newString = null;

                        // Let's give this 100 tries.
                        for ($counter = 1; $counter <= 100; $counter++) {
                            if ($type === 'handle') {
                                $newString = $duplicate[$type].$counter;
                            } else {
                                $newString = $duplicate[$type].' '.$counter;
                            }

                            $exists = (new Query())
                                ->from(['{{%usergroups}}'])
                                ->where([$type => $newString])
                                ->exists($this->db);

                            // Found a free one.
                            if (!$exists) {
                                break;
                            }
                        }

                        // Let's update with a unique one.
                        $this->update('{{%usergroups}}', [$type => $newString], ['id' => $row['id']]);
                    }
                }
            }
        } else {
            echo " none found\n";
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160920_231045_usergroup_handle_title_unique cannot be reverted.\n";

        return false;
    }
}
