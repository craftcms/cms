<?php

declare(strict_types=1);

use craft\base\Element as LegacyElement;
use craft\base\ElementInterface as LegacyElementInterface;
use craft\base\Event as YiiEvent;
use craft\elements\Asset as LegacyAsset;
use craft\elements\Entry as LegacyEntry;
use craft\events\ModelEvent;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Events\ElementLifecycleSaved;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Facades\Event;

uses(TestCase::class);

afterEach(function(): void {
    foreach ([LegacyElement::class, LegacyElementInterface::class, LegacyEntry::class, LegacyAsset::class, Element::class] as $class) {
        YiiEvent::off($class, LegacyElement::EVENT_AFTER_SAVE);
    }
});

it('calls each class-level handler once, most specific class first', function(): void {
    $calls = [];

    foreach ([LegacyElement::class, LegacyElementInterface::class, LegacyEntry::class, LegacyAsset::class, Element::class] as $class) {
        YiiEvent::on($class, LegacyElement::EVENT_AFTER_SAVE, function(ModelEvent $event) use (&$calls, $class): void {
            $calls[] = [$class, $event->isNew];
        });
    }

    Event::dispatch(new ElementLifecycleSaved(new Entry(), true));

    expect($calls)->toBe([
        [LegacyEntry::class, true],
        [LegacyElement::class, true],
        [Element::class, true],
        [LegacyElementInterface::class, true],
    ]);
});

it('stops calling handlers once one marks the event handled', function(): void {
    $calls = [];

    YiiEvent::on(LegacyEntry::class, LegacyElement::EVENT_AFTER_SAVE, function(ModelEvent $event) use (&$calls): void {
        $calls[] = LegacyEntry::class;
        $event->handled = true;
    });
    YiiEvent::on(LegacyElement::class, LegacyElement::EVENT_AFTER_SAVE, function() use (&$calls): void {
        $calls[] = LegacyElement::class;
    });

    Event::dispatch(new ElementLifecycleSaved(new Entry(), false));

    expect($calls)->toBe([LegacyEntry::class]);
});
