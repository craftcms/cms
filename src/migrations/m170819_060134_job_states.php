<?php

namespace craft\migrations;

use Craft;
use craft\db\Migration;

/**
 * m170819_060134_job_states migration.
 */
class m170819_060134_job_states extends Migration
{
    /**
     * @inheritdoc
     */
    public function safeUp()
    {
        // provides retrievable ongoing job operations notes
        Craft::$app->getDb()->createCommand()
            ->addColumn(
                '{{%queue}}',
                'state',
                $this->text()->after('description'))
            ->execute();

        // allows selecting jobs queued by owner
        Craft::$app->getDb()->createCommand()
            ->addColumn(
                '{{%queue}}',
                'owner',
                $this->text()->after('state'))
            ->execute();
    }

    /**
     * @inheritdoc
     */
    public function safeDown()
    {
        Craft::$app->getDb()->createCommand()
            ->dropColumn(
                '{{%queue}}',
                'status')
            ->execute();

        Craft::$app->getDb()->createCommand()
            ->dropColumn(
                '{{%queue}}',
                'owner')
            ->execute();

        return true;
    }
}
