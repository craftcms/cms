<?php

namespace craft\contentmigrations;

use Craft;
use craft\db\Migration;

/**
 * m170818_221819_job_status migration.
 */
class m170818_221819_job_status extends Migration
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
