<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Events\ElementExportersResolving;
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

    test('event provides source key', function () {
        $capturedSource = null;

        Event::listen(function (ElementExportersResolving $event) use (&$capturedSource) {
            $capturedSource = $event->source;
        });

        Entry::exporters('section:my-section');

        expect($capturedSource)->toBe('section:my-section');
    });

    test('event can modify exporters', function () {
        Event::listen(function (ElementExportersResolving $event) {
            if ($event->elementType === Entry::class) {
                $event->exporters = [Raw::class];
            }
        });

        $exporters = Entry::exporters('*');

        expect($exporters)->toBe([Raw::class]);
    });
});
