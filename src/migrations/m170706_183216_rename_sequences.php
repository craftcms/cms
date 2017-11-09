<?php

namespace craft\migrations;

use craft\db\Migration;

/**
 * m170706_183216_rename_sequences migration.
 */
class m170706_183216_rename_sequences extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        if (!$this->db->getIsPgsql()) {
            return;
        }

        // Make sure any old sequences have been renamed to match the new table names
        // (see https://www.postgresql.org/message-id/200308211224.06775.jgardner%40jonathangardner.net)
        $sequences = [
            'emailmessages' => 'systemmessages',
            'categorygroups_i18n' => 'categorygroups_sites',
            'elements_i18n' => 'elements_sites',
            'sections_i18n' => 'sections_sites',
        ];

        foreach ($sequences as $oldName => $newName) {
            $oldName = $this->db->tablePrefix.$oldName.'_id_seq';
            $newName = $this->db->tablePrefix.$newName.'_id_seq';

            $transaction = $this->db->beginTransaction();
            try {
                $this->renameSequence($oldName, $newName);
                $transaction->commit();
            } catch (\Throwable $e) {
                // Silently fail. The sequence probably doesn't exist
                $transaction->rollBack();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m170706_183216_rename_sequences cannot be reverted.\n";
        return false;
    }
}
