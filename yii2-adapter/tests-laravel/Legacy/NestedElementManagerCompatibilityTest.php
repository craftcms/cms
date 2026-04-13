<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\base\Event as YiiEvent;
use craft\elements\NestedElementManager as LegacyNestedElementManager;
use craft\events\BulkElementsEvent as LegacyBulkElementsEvent;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\AfterSaveNestedElements;
use CraftCms\Yii2Adapter\Tests\TestCase;
use Illuminate\Support\Facades\Event;

class NestedElementManagerCompatibilityTest extends TestCase
{
    public function testLegacyNestedElementManagerClassEventListenersReceiveLegacyPayloads(): void
    {
        $manager = new LegacyNestedElementManager(
            Address::class,
            fn(ElementInterface $owner) => Address::find(),
            ['attribute' => 'addresses'],
        );

        $receivedEvent = null;

        YiiEvent::on(LegacyNestedElementManager::class, LegacyNestedElementManager::EVENT_AFTER_SAVE_ELEMENTS, function(LegacyBulkElementsEvent $event) use (&$receivedEvent) {
            $receivedEvent = $event;
        });

        try {
            Event::dispatch(new AfterSaveNestedElements($manager, []));

            self::assertInstanceOf(LegacyBulkElementsEvent::class, $receivedEvent);
            self::assertSame([], $receivedEvent->elements);
            self::assertSame($manager, $receivedEvent->sender);
        } finally {
            YiiEvent::off(LegacyNestedElementManager::class, LegacyNestedElementManager::EVENT_AFTER_SAVE_ELEMENTS);
        }
    }
}
