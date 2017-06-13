<?php

namespace craft\migrations;

use craft\db\Migration;
use craft\helpers\MigrationHelper;

/**
 * m170612_000000_route_index_shuffle migration.
 */
class m170612_000000_route_index_shuffle extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        echo "    > Dropping `urlPattern` (unique) index on the routes table.\n";
        MigrationHelper::dropIndexIfExists('{{%routes}}', ['urlPattern'], true, $this);

        echo "    > Creating `routes` index on the routes table.\n";
        $this->createIndex(null, '{{%routes}}', 'urlPattern');

        return true;
    }
}
