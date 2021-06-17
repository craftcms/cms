<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
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

        $uids = (new Query())
            ->select(['uid'])
            ->from([Table::GLOBALSETS])
            ->orderBy(['name' => SORT_ASC])
            ->column();

        foreach ($uids as $i => $uid) {
            $projectConfig->set(Globals::CONFIG_GLOBALSETS_KEY . ".$uid.sortOrder", $i + 1);
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
