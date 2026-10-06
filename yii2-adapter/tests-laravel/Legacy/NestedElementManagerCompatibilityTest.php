<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\base\Event as YiiEvent;
use craft\elements\NestedElementManager as LegacyNestedElementManager;
use craft\events\BulkElementsEvent as LegacyBulkElementsEvent;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Events\NestedElementsSaved;
use CraftCms\Cms\Element\NestedElementManager;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\View\Enums\Position;
use CraftCms\Yii2Adapter\Tests\TestCase;
use CraftCms\Yii2Adapter\View\LegacyAssets\CpCompatAsset;
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
            Event::dispatch(new NestedElementsSaved($manager, []));

            self::assertInstanceOf(LegacyBulkElementsEvent::class, $receivedEvent);
            self::assertSame([], $receivedEvent->elements);
            self::assertSame($manager, $receivedEvent->sender);
        } finally {
            YiiEvent::off(LegacyNestedElementManager::class, LegacyNestedElementManager::EVENT_AFTER_SAVE_ELEMENTS);
        }
    }
    public function testCollapsedMatrixEntriesAreRememberedOnTheNextPage(): void
    {
        $consoleState = new \ReflectionProperty($this->app, 'isRunningInConsole');
        $previous = $consoleState->getValue($this->app);
        $consoleState->setValue($this->app, false);
        YiiEvent::on(LegacyNestedElementManager::class, LegacyNestedElementManager::EVENT_AFTER_SAVE_ELEMENTS, function(LegacyBulkElementsEvent $event) {
            $event->elements[0]->collapsed = true;
        });

        try {
            foreach ([Matrix::class, \craft\fields\Matrix::class] as $fieldClass) {
                session()->forget(['__js', '__ab']);
                $manager = new NestedElementManager(Entry::class, fn(ElementInterface $owner) => Entry::find(), [
                    'field' => new $fieldClass(['handle' => 'blocks']),
                ]);
                $entries = [new Entry(['id' => 91, 'collapsed' => false]), new Entry(['id' => 92, 'collapsed' => false])];

                Event::dispatch(new NestedElementsSaved($manager, $entries));

                self::assertSame([
                    ['Craft.MatrixInput.rememberCollapsedEntryId(91);', Position::BodyEnd->value, null],
                ], session()->getJs(false));
                self::assertSame([CpCompatAsset::class], session()->get('__ab'));
            }

            $manager = new NestedElementManager(Address::class, fn(ElementInterface $owner) => Address::find(), ['attribute' => 'addresses']);
            Event::dispatch(new NestedElementsSaved($manager, $entries));

            self::assertCount(1, session()->getJs());
        } finally {
            YiiEvent::off(LegacyNestedElementManager::class, LegacyNestedElementManager::EVENT_AFTER_SAVE_ELEMENTS);
            $consoleState->setValue($this->app, $previous);
        }
    }
}
