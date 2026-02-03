<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\RegisterFieldLayouts;
use CraftCms\Cms\Element\Events\RegisterSources;
use CraftCms\Cms\Entry\Elements\Entry;

class TestHasSourcesElement extends Element
{
    #[\Override]
    public static function displayName(): string
    {
        return 'Test Element';
    }
}

describe('multiPageSources', function () {
    test('returns false by default', function () {
        expect(TestHasSourcesElement::multiPageSources())->toBeFalse();
    });
});

describe('sources', function () {
    test('returns array for context', function (string $context) {
        expect(Entry::sources($context))->toBeArray();
    })->with(['index', 'modal', 'field', 'settings']);

    test('memoizes results for same class and context', function () {
        $sources1 = Entry::sources('index');
        $sources2 = Entry::sources('index');

        expect($sources1)->toBe($sources2);
    });

    test('triggers RegisterSources event', function () {
        $eventTriggered = false;

        \Illuminate\Support\Facades\Event::listen(function (RegisterSources $event) use (&$eventTriggered) {
            if ($event->elementType === TestHasSourcesElement::class) {
                $eventTriggered = true;
                $event->sources = [];
            }
        });

        TestHasSourcesElement::sources('modal');

        expect($eventTriggered)->toBeTrue();
    });
});

describe('findSource', function () {
    test('returns null by default', function () {
        $result = Entry::findSource('nonexistent', 'index');

        expect($result)->toBeNull();
    });
});

describe('sourcePath', function () {
    test('returns null by default', function () {
        $result = Entry::sourcePath('source', 'step', 'index');

        expect($result)->toBeNull();
    });
});

describe('modifyCustomSource', function () {
    test('returns config unchanged by default', function () {
        $config = ['key' => 'value', 'nested' => ['array' => true]];
        $result = Entry::modifyCustomSource($config);

        expect($result)->toBe($config);
    });
});

describe('fieldLayouts', function () {
    test('returns array', function () {
        $layouts = Entry::fieldLayouts(null);

        expect($layouts)->toBeArray();
    });

    test('triggers RegisterFieldLayouts event', function () {
        $eventTriggered = false;

        \Illuminate\Support\Facades\Event::listen(function (RegisterFieldLayouts $event) use (&$eventTriggered) {
            if ($event->elementType === Entry::class) {
                $eventTriggered = true;
                $event->fieldLayouts = [];
            }
        });

        Entry::fieldLayouts(null);

        expect($eventTriggered)->toBeTrue();
    });
});
