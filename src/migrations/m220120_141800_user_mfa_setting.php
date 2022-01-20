<?php

namespace craft\migrations;

use Craft;
use craft\base\ElementInterface;
use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Json;
use craft\services\ElementSources;
use craft\services\ProjectConfig;

/**
 * m220120_141800_user_mfa_setting migration.
 */
class m220120_141800_user_mfa_setting extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp(): bool
    {
        $this->addColumn(Table::USERS, 'enable2fa', $this->boolean()->defaultValue(false)->after('password'));
        return true;
    }

    /**
     * @inheritdoc
     */
    public function safeDown(): bool
    {
        echo "m220120_141800_user_mfa_setting cannot be reverted.\n";
        return false;
    }
}
