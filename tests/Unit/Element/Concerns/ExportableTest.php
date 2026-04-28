<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\RegisterExporters;
use CraftCms\Cms\Element\Exporters\Expanded;
use CraftCms\Cms\Element\Exporters\Raw;
use CraftCms\Cms\Entry\Elements\Entry;
use Illuminate\Support\Facades\Event;

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

    test('RegisterExporters event allows adding custom exporters', function () {
        $customExporter = new class
        {
            public static function displayName(): string
            {
                return 'Custom';
            }
        };

        Event::listen(function (RegisterExporters $event) use ($customExporter) {
            if ($event->elementType === Entry::class) {
                $event->exporters[] = $customExporter::class;
            }
        });

        $exporters = Entry::exporters('*');

        expect($exporters)->toContain(Raw::class);
        expect($exporters)->toContain(Expanded::class);
        expect($exporters)->toContain($customExporter::class);
    });

    test('event provides source key', function () {
        $capturedSource = null;

        Event::listen(function (RegisterExporters $event) use (&$capturedSource) {
            $capturedSource = $event->source;
        });

        Entry::exporters('section:my-section');

        expect($capturedSource)->toBe('section:my-section');
    });

    test('event can modify exporters', function () {
        Event::listen(function (RegisterExporters $event) {
            if ($event->elementType === Entry::class) {
                $event->exporters = [Raw::class];
            }
        });

        $exporters = Entry::exporters('*');

        expect($exporters)->toBe([Raw::class]);
    });

    test('event can remove all exporters', function () {
        Event::listen(function (RegisterExporters $event) {
            if ($event->elementType === Entry::class) {
                $event->exporters = [];
            }
        });

        $exporters = Entry::exporters('*');

        expect($exporters)->toBe([]);
    });
});
