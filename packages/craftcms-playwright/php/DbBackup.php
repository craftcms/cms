<?php

declare(strict_types=1);

namespace modules;

use Craft;
use craft\db\Connection;
use craft\db\Table;
use craft\events\BackupEvent;
use craft\helpers\ArrayHelper;
use CraftCms\Aliases\Aliases;
use yii\base\Event;
use yii\base\Module;

class DbBackup extends Module
{
    #[\Override]
    public function init()
    {
        // Set a @modules alias pointed to the modules/ directory
        Aliases::set('@modules', __DIR__);
        parent::init();

        Craft::$app->onInit(function () {
            Event::on(
                Connection::class,
                Connection::EVENT_BEFORE_CREATE_BACKUP,
                function (BackupEvent $event) {
                    ArrayHelper::removeValue($event->ignoreTables, Table::SESSIONS);
                }
            );
        });
    }
}
