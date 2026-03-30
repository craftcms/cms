<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy;

use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Yii2Adapter\Tests\TestCase;

class FieldTranslationMethodCompatibilityTest extends TestCase
{
    public function testLegacyFieldAcceptsStringTranslationMethodAssignment(): void
    {
        $field = new class() extends \craft\base\Field {
        };

        $field->translationMethod = 'site';

        self::assertSame('site', $field->translationMethod);
        self::assertSame('site', $field->translationMethodValue);
        self::assertSame(TranslationMethod::Site->description(), $field->getTranslationDescription(null));
    }

    public function testLegacyFieldFallsBackForInvalidTranslationMethodAssignment(): void
    {
        $field = new class() extends \craft\base\Field {
        };

        $field->translationMethod = 'invalid';

        self::assertSame(TranslationMethod::None->value, $field->translationMethod);
        self::assertSame(TranslationMethod::None->value, $field->translationMethodValue);
        self::assertNull($field->getTranslationDescription(null));
    }
}
