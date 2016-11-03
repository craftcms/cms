<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;

/**
 * m161103_124145_index_shuffle migration.
 */
class m161103_124145_index_shuffle extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Order is important
        Craft::info('Dropping `expiryDate,cacheKey,locale,path` index on the templatecaches table.', __METHOD__);
        $this->dropIndex($this->db->getIndexName('{{%templatecaches}}', 'expiryDate,cacheKey,locale,path'), '{{%templatecaches}}');

        Craft::info('Creating `locale,cacheKey,path,expiryDate` index on the templatecaches table.', __METHOD__);
        $this->createIndex($this->db->getIndexName('{{%templatecaches}}', 'locale,cacheKey,path,expiryDate'), '{{%templatecaches}}', 'locale,cacheKey,path,expiryDate');
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
