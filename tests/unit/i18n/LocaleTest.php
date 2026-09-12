<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\i18n;

use craft\i18n\Locale;
use craft\test\TestCase;

/**
 * Unit tests for the Locale class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.12.0
 */
class LocaleTest extends TestCase
{
    /**
     * @param string $expected
     * @param string $locale
     * @dataProvider languageIdDataProvider
     */
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
