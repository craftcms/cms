<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\FileHelper;
use craft\helpers\Path as PathHelper;
use craft\helpers\ProjectConfig;
use craft\services\Sections;
use craft\services\UserGroups;
use Symfony\Component\Yaml\Yaml;

/**
 * m200630_183000_drop_configmap migration.
 */
class m200630_183000_drop_configmap extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        $this->dropColumn(Table::INFO, 'configMap');
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        echo "m200630_183000_drop_configmap cannot be reverted.\n";
        return false;
    }
}
