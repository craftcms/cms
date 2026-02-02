<?php

declare(strict_types=1);

use craft\events\RegisterElementFieldLayoutsEvent;
use craft\events\RegisterElementSourcesEvent;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Entry\Elements\Entry;
use yii\base\Event;

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

    test('triggers registerSources event', function () {
        $eventTriggered = false;
        $handler = function (RegisterElementSourcesEvent $event) use (&$eventTriggered) {
            $eventTriggered = true;
            $event->sources = [];
        };

        Event::on(TestHasSourcesElement::class, TestHasSourcesElement::EVENT_REGISTER_SOURCES, $handler);
        TestHasSourcesElement::sources('index');
        Event::off(TestHasSourcesElement::class, TestHasSourcesElement::EVENT_REGISTER_SOURCES, $handler);

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

    test('triggers registerFieldLayouts event', function () {
        $eventTriggered = false;
        $handler = function (RegisterElementFieldLayoutsEvent $event) use (&$eventTriggered) {
            $eventTriggered = true;
            $event->fieldLayouts = [];
        };

        Event::on(Entry::class, Entry::EVENT_REGISTER_FIELD_LAYOUTS, $handler);
        Entry::fieldLayouts(null);
        Event::off(Entry::class, Entry::EVENT_REGISTER_FIELD_LAYOUTS, $handler);

        expect($eventTriggered)->toBeTrue();
    });
});
