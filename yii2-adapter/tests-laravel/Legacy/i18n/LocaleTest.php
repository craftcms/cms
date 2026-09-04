<?php

declare(strict_types=1);

namespace CraftCms\Yii2Adapter\Tests\Legacy\i18n;

use craft\i18n\Locale;
use Orchestra\Testbench\PHPUnit\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class LocaleTest extends TestCase
{
    #[DataProvider('languageIdDataProvider')]
    public function testLanguageId(string $expected, string $locale): void
    {
        self::assertSame($expected, Locale::languageId($locale));
    }

    /**
     * @return array[]
     */
    public static function languageIdDataProvider(): array
    {
        return [
            ['en', 'en'],
            ['en', 'EN'],
            ['en', 'en-US'],
            ['en', 'EN-US'],
            ['zh', 'zh-Hans-CN'],
            ['de', 'de-DE'],
            ['', ''],
            ['pt', 'pt-BR'],
        ];
    }
}
