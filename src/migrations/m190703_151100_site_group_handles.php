<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\helpers\StringHelper;

/**
 * m190703_151100_site_group_handles migration.
 */
class m190703_151100_site_group_handles extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->addColumn('{{%sitegroups}}', 'handle', $this->string());

        // Set handles on all.
        $siteGroups = Craft::$app->getSites()->getAllGroups();
        foreach ($siteGroups as $siteGroup) {
            $siteGroup->handle = StringHelper::toCamelCase($siteGroup->name);

            Craft::$app->getSites()->saveGroup($siteGroup);

            // Rush the DB so we can set the notNull() constraint
            Craft::$app->getDb()->createCommand()
                ->update('{{%sitegroups}}', [
                    'handle' => $siteGroup->handle
                ], [
                    'id' => $siteGroup->id
                ]);
        }

        // *Now* this is fine.
        $this->alterColumn('{{%sitegroups}}', 'handle', $this->string()->notNull());
        $this->createIndex(null, '{{%sitegroups}}', ['handle'], true);

    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m190703_151100_site_group_handles cannot be reverted.\n";
        return false;
    }
}
