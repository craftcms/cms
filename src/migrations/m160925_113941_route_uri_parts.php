<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

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
        MigrationHelper::dropIndexIfExists('{{%routes}}', 'urlPattern', false, $this);

        $this->renameColumn('{{%routes}}', 'urlParts', 'uriParts');
        $this->renameColumn('{{%routes}}', 'urlPattern', 'uriPattern');

        $this->createIndex(null, '{{%routes}}', ['uriPattern']);
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
