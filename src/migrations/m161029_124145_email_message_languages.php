<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\MigrationHelper;

/**
 * m161029_124145_email_message_languages migration.
 */
class m161029_124145_email_message_languages extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // This will only apply to installs coming from earlier versions of the Craft 3 Dev Preview
        if (!$this->db->columnExists('{{%emailmessages}}', 'siteId')) {
            return;
        }

        // Add the new language column and populate from site/language mapping
        $this->addColumn('{{%emailmessages}}', 'language', $this->string()->after('id'));

        $siteResults = (new Query())
            ->select(['id', 'language'])
            ->from(Table::SITES)
            ->all($this->db);

        $siteLanguages = ArrayHelper::map($siteResults, 'id', 'language');

        $messageResults = (new Query())
            ->select(['id', 'siteId', 'key'])
            ->from('{{%emailmessages}}')
            ->all($this->db);

        $handledLanguagesByKey = [];

        foreach ($messageResults as $messageResult) {
            $language = $siteLanguages[$messageResult['siteId']];
            if (isset($handledLanguagesByKey[$messageResult['key']][$language])) {
                // Delete this message since we already have one for this language
                $this->delete('{{%emailmessages}}', ['id' => $messageResult['id']]);
            } else {
                $this->update('{{%emailmessages}}', ['language' => $language], ['id' => $messageResult['id']]);
                $handledLanguagesByKey[$messageResult['key']][$language] = true;
            }
        }

        // Make the languages column NOT NULL and add indexes to it
        $this->alterColumn('{{%emailmessages}}', 'language', $this->string()->notNull());
        $this->createIndex(null, '{{%emailmessages}}', ['key', 'language'], true);
        $this->createIndex(null, '{{%emailmessages}}', ['language'], false);

        // Drop the siteId column
        MigrationHelper::dropForeignKeyIfExists('{{%emailmessages}}', ['siteId'], $this);
        MigrationHelper::dropIndexIfExists('{{%emailmessages}}', ['key', 'siteId'], true, $this);
        MigrationHelper::dropIndexIfExists('{{%emailmessages}}', ['siteId'], false, $this);
        $this->dropColumn('{{%emailmessages}}', 'siteId');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m161029_124145_email_message_languages cannot be reverted.\n";

        return false;
    }
}
