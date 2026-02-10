<?php

declare(strict_types=1);

use craft\elements\exporters\Expanded;
use craft\elements\exporters\Raw;
use craft\events\RegisterElementExportersEvent;
use CraftCms\Cms\Entry\Elements\Entry;
use yii\base\Event;

describe('exporters', function () {
    test('returns default exporters', function () {
        $exporters = Entry::exporters('*');

        expect($exporters)->toBe([
            Raw::class,
            Expanded::class,
        ]);
    });

    test('returns exporters for specific source', function () {
        $exporters = Entry::exporters('section:12345');

        expect($exporters)->toBe([
            Raw::class,
            Expanded::class,
        ]);
    });

    test('triggers registerExporters event', function () {
        $eventTriggered = false;
        $customExporter = new class
        {
            public static function displayName(): string
            {
                return 'Custom';
            }
        };

        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_EXPORTERS,
            function (RegisterElementExportersEvent $event) use (&$eventTriggered, $customExporter) {
                $eventTriggered = true;
                $event->exporters[] = $customExporter::class;
            }
        );

        $exporters = Entry::exporters('*');

        expect($eventTriggered)->toBeTrue();
        expect($exporters)->toContain(Raw::class);
        expect($exporters)->toContain(Expanded::class);
        expect($exporters)->toContain($customExporter::class);

        Event::off(Entry::class, Entry::EVENT_REGISTER_EXPORTERS);
    });

    test('event provides source key', function () {
        $capturedSource = null;

        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_EXPORTERS,
            function (RegisterElementExportersEvent $event) use (&$capturedSource) {
                $capturedSource = $event->source;
            }
        );

        Entry::exporters('section:my-section');

        expect($capturedSource)->toBe('section:my-section');

        Event::off(Entry::class, Entry::EVENT_REGISTER_EXPORTERS);
    });

    test('event can modify exporters', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_EXPORTERS,
            function (RegisterElementExportersEvent $event) {
                $event->exporters = [Raw::class];
            }
        );

        $exporters = Entry::exporters('*');

        expect($exporters)->toBe([Raw::class]);

        Event::off(Entry::class, Entry::EVENT_REGISTER_EXPORTERS);
    });

    test('event can remove all exporters', function () {
        Event::on(
            Entry::class,
            Entry::EVENT_REGISTER_EXPORTERS,
            function (RegisterElementExportersEvent $event) {
                $event->exporters = [];
            }
        );

        $exporters = Entry::exporters('*');

        expect($exporters)->toBe([]);

        Event::off(Entry::class, Entry::EVENT_REGISTER_EXPORTERS);
    });
});
