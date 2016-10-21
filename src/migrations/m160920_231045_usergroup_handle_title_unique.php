<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\db\Query;
use craft\app\helpers\MigrationHelper;

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
            $this->createIndex($this->db->getIndexName('{{%usergroups}}', 'handle', true), '{{%usergroups}}', 'handle', true);
        }

        if (!MigrationHelper::doesIndexExist('{{%usergroups}}', 'name', true)) {
            $this->createIndex($this->db->getIndexName('{{%usergroups}}', 'name', true), '{{%usergroups}}', 'name', true);
        }
    }

    /**
     * @param string $type Either 'handle' or 'name'
     */
    private function _handleDupes($type)
    {
        echo '    > looking for duplicate user group '.$type.'s ...';
        // Get any duplicates.
        $duplicates = (new Query())
            ->select($type)
            ->from('{{%usergroups}}')
            ->groupBy($type)
            ->having('count("'.$type.'") > 1')
            ->all();

        if ($duplicates) {
            echo ' found '.count($duplicates)."\n";

            foreach ($duplicates as $duplicate) {
                echo '    > fixing duplicate "'.$duplicate[$type].'" user group '.$type."s\n";

                $rows = (new Query())
                    ->from('{{%usergroups}}')
                    ->where($type.'=:type', ['type' => $duplicate[$type]])
                    ->orderBy('dateCreated')
                    ->all();

                // Find anything?
                if ($rows) {
                    // Skip the first (the earliest created), since presumably it's the good one.
                    unset($rows[0]);

                    if ($rows) {
                        foreach ($rows as $row) {
                            $newString = null;

                            // Let's give this 100 tries.
                            for ($counter = 1; $counter <= 100; $counter++) {
                                if ($type == 'handle') {
                                    $newString = $duplicate[$type].$counter;
                                } else {
                                    $newString = $duplicate[$type].' '.$counter;
                                }

                                $exists = (new Query())
                                    ->from('{{%usergroups}}')
                                    ->where($type.'=:type', array('type' => $newString))
                                    ->exists();

                                // Found a free one.
                                if (!$exists) {
                                    break;
                                }
                            }

                            // Let's update with a unique one.
                            $this->update(
                                '{{%usergroups}}',
                                [$type => $newString],
                                ['id' => $row['id']]
                            );
                        }
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
