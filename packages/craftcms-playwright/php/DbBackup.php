<?php

namespace modules;

use Craft;
use craft\db\Connection;
use craft\db\Table;
use craft\events\BackupEvent;
use craft\helpers\ArrayHelper;
use yii\base\Event;

class DbBackup extends \yii\base\Module
{
    public function init()
    {
        // Set a @modules alias pointed to the modules/ directory
        Craft::setAlias('@modules', __DIR__);
        parent::init();

        Craft::$app->onInit(function() {
            Event::on(
                Connection::class,
                Connection::EVENT_BEFORE_CREATE_BACKUP,
                function(BackupEvent $event) {
                    ArrayHelper::removeValue($event->ignoreTables, Table::SESSIONS);
                }
            );
        });
    }
}
