<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use yii\helpers\Inflector;

/**
 * m200806_084715_add_site_group_handle migration.
 */
class m200806_084715_add_site_group_handle extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // Make table changes
        $this->addColumn(Table::SITEGROUPS, 'handle', $this->string()->after('name'));

        $this->createIndex(null, Table::SITEGROUPS, ['handle'], true);

        // Update existing Site Groups handle
        $siteGroups = (new Query())
            ->select(['id', 'name'])
            ->from(Table::SITEGROUPS)
            ->all();

        foreach($siteGroups as $siteGroup) {
            $this->update(Table::SITEGROUPS, [
                'handle' => Inflector::variablize($siteGroup['name'])
            ], [
                'id' => $siteGroup['id']
            ], [], false);
        }

        // Set the new column to be NOT NULL
        $this->alterColumn(Table::SITEGROUPS, 'handle', $this->string()->notNull());
    }

    public function safeDown()
    {
        echo "m200806_084715_add_site_group_handle cannot be reverted.\n";
        return false;
    }
}
