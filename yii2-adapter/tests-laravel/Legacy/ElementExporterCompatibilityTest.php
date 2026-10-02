<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use Craft;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Yii2Adapter\Tests\TestCase;
use yii\base\Event as YiiEvent;

class ElementExporterCompatibilityTest extends TestCase
{
    public function testLegacyExporterClassNamesStillInstantiate(): void
    {
        $exporter = Craft::$app->getElements()->createExporter(\craft\elements\exporters\Raw::class);

        self::assertInstanceOf(\CraftCms\Cms\Element\Exporters\Raw::class, $exporter);
    }

    public function testLegacyElementExporterBaseClassStillWorks(): void
    {
        $exporter = new class() extends \craft\base\ElementExporter {
            public function export(\CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface $query): mixed
            {
                return [];
            }
        };

        $exporter->setElementType(Entry::class);

        self::assertSame('entries', $exporter->getFilename());
        self::assertInstanceOf(\CraftCms\Cms\Element\Contracts\ElementExporterInterface::class, $exporter);
    }

    public function testLegacyRegisterExportersHandlersBridgeIntoRegisterExporters(): void
    {
        YiiEvent::on(Entry::class, \craft\base\Element::EVENT_REGISTER_EXPORTERS, function(\craft\events\RegisterElementExportersEvent $event) {
            $event->exporters = [\craft\elements\exporters\Raw::class];
        });

        self::assertSame([\craft\elements\exporters\Raw::class], Entry::exporters('*'));
    }
}
