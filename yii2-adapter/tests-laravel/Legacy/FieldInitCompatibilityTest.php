<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use craft\base\Field;
use craft\elements\Entry;
use craft\fields\BaseRelationField;
use CraftCms\Yii2Adapter\Tests\TestCase;

class FieldInitCompatibilityTest extends TestCase
{
    public function testLegacyFieldsInitAfterTheyAreConfigured(): void
    {
        $field = new class(['handle' => 'hours']) extends Field {
            public ?string $initializedHandle = null;

            public function init(): void
            {
                parent::init();
                $this->initializedHandle = $this->handle;
            }
        };

        $relationField = new class(['handle' => 'related']) extends BaseRelationField {
            public ?string $initializedHandle = null;

            public static function elementType(): string
            {
                return Entry::class;
            }

            public function init(): void
            {
                parent::init();
                $this->initializedHandle = $this->handle;
            }
        };

        self::assertSame('hours', $field->initializedHandle);
        self::assertSame('related', $relationField->initializedHandle);
    }
}
