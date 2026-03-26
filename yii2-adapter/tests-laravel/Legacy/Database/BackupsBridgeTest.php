<?php

declare(strict_types=1);

use craft\base\Event as YiiEvent;
use craft\db\Connection;
use craft\events\BackupEvent;
use CraftCms\Cms\Database\Events\AfterRestoreBackup;
use CraftCms\Cms\Database\Events\BeforeCreateBackup;

it('bridges before create backup events to legacy handlers', function() {
    $handlerCalls = 0;
    $receivedFile = null;
    $receivedIgnoreTables = null;

    $handler = function(BackupEvent $event) use (&$handlerCalls, &$receivedFile, &$receivedIgnoreTables) {
        $handlerCalls++;
        $receivedFile = $event->file;
        $receivedIgnoreTables = $event->ignoreTables;
        $event->ignoreTables[] = 'custom_table';
    };

    YiiEvent::on(Connection::class, Connection::EVENT_BEFORE_CREATE_BACKUP, $handler);

    try {
        $connection = \Craft::$app->getDb()->getLaravelConnection();

        $event = new BeforeCreateBackup(
            connection: $connection,
            file: '/tmp/backup.sql',
            ignoreTables: ['cache'],
        );

        event($event);

        expect($handlerCalls)->toBe(1);
        expect($receivedFile)->toBe('/tmp/backup.sql');
        expect($receivedIgnoreTables)->toBe(['cache']);
        expect($event->ignoreTables)->toContain('custom_table');
    } finally {
        YiiEvent::off(Connection::class, Connection::EVENT_BEFORE_CREATE_BACKUP, $handler);
    }
});

it('keeps after restore backup legacy event payload compatibility', function() {
    $receivedEvent = null;

    $handler = function(BackupEvent $event) use (&$receivedEvent) {
        $receivedEvent = $event;
    };

    YiiEvent::on(Connection::class, Connection::EVENT_AFTER_RESTORE_BACKUP, $handler);

    try {
        $connection = \Craft::$app->getDb()->getLaravelConnection();

        event(new AfterRestoreBackup(
            connection: $connection,
            file: '/tmp/restore.sql',
        ));

        expect($receivedEvent)->toBeInstanceOf(BackupEvent::class);
        expect($receivedEvent->file)->toBe('/tmp/restore.sql');
    } finally {
        YiiEvent::off(Connection::class, Connection::EVENT_AFTER_RESTORE_BACKUP, $handler);
    }
});
