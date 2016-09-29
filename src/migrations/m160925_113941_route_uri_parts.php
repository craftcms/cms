<?php

namespace craft\app\migrations;

use Craft;
use craft\app\db\Migration;
use craft\app\helpers\MigrationHelper;

/**
 * m160925_113941_route_uri_parts migration.
 */
class m160925_113941_route_uri_parts extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        MigrationHelper::dropIndexIfExists('{{%routes}}', 'urlPattern', true, $this);

        $this->renameColumn('{{%routes}}', 'urlParts', 'uriParts');
        $this->renameColumn('{{%routes}}', 'urlPattern', 'uriPattern');

        $this->createIndex($this->db->getIndexName('{{%routes}}', 'uriPattern', true), '{{%routes}}', 'uriPattern', true);
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m160925_113941_route_uri_parts cannot be reverted.\n";
        return false;
    }
}
