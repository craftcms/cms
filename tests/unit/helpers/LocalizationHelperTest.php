<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use craft\helpers\Localization;
use craft\test\TestCase;
use UnitTester;
use yii\base\InvalidArgumentException;

/**
 * Class LocalizationHelper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class LocalizationHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    /**
     * @dataProvider normalizeLanguageDataProvider
     * @param string $expected
     * @param string $language
     */
    public function testNormalizeLanguage(string $expected, string $language): void
    {
        self::assertSame($expected, Localization::normalizeLanguage($language));
    }

    /**
     *
     */
    public function testLanguageNormalizationExceptions(): void
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            Localization::normalizeLanguage('dutch');
        });
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            Localization::normalizeLanguage('notalang');
        });
    }

    /**
     * @dataProvider normalizeNumberDataProvider
     * @param mixed $expected
     * @param mixed $number
     * @param string|null $localeId
     */
    public function testNormalizeNumber(mixed $expected, mixed $number, ?string $localeId): void
    {
        self::assertSame($expected, Localization::normalizeNumber($number, $localeId));
    }

    /**
     * @return array
     */
    public function normalizeLanguageDataProvider(): array
    {
        return [
            ['nl', 'nl'],
            ['en-US', 'en-US'],
            ['af', 'af'],
            ['af-NA', 'af-NA'],
            ['en-AG', 'en-ag'],
            ['en-AG', 'EN-AG'],
        ];
    }

    /**
     * @return array
     */
    public function normalizeNumberDataProvider(): array
    {
        return [
            ['2000000000', '20,0000,0000', null],
            ['20 0000 0000', '20 0000 0000', null],
            ['20.0000.0000', '20.0000.0000', null],
            [2000000000, 2000000000, null],
        ];
    }
}
