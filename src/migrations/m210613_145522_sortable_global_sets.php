<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Table;
use craft\helpers\ArrayHelper;
use craft\helpers\MigrationHelper;
use craft\services\Globals;

/**
 * m210613_145522_sortable_global_sets migration.
 */
class m210613_145522_sortable_global_sets extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn(Table::GLOBALSETS, 'sortOrder', $this->smallInteger()->unsigned()->after('fieldLayoutId'));
        $this->createIndex(null, Table::GLOBALSETS, ['sortOrder'], false);

        $projectConfig = Craft::$app->getProjectConfig();
        $schemaVersion = $projectConfig->get('system.schemaVersion', true);

        if (version_compare($schemaVersion, '3.7.6', '>=')) {
            return;
        }

        $globalSets = $projectConfig->get(Globals::CONFIG_GLOBALSETS_KEY) ?? [];
        ArrayHelper::multisort($globalSets, 'name');
        $sortOrder = 0;

        foreach ($globalSets as $uid => $data) {
            $projectConfig->set(Globals::CONFIG_GLOBALSETS_KEY . ".$uid.sortOrder", ++$sortOrder);
        }
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        MigrationHelper::dropIndexIfExists(Table::GLOBALSETS, ['sortOrder'], false, $this);
        $this->dropColumn(Table::GLOBALSETS, 'sortOrder');
    }
}
