<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\ArrayHelper;
use craft\helpers\Localization;
use UnitTester;
use yii\base\InvalidArgumentException;

/**
 * Class LocalizationHelper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class LocalizationHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @dataProvider normalizeLanguageDataProvider
     *
     * @param string $expected
     * @param string $language
     * @param bool $skipIfNoIntl
     */
    public function testNormalizeLanguage(string $expected, string $language, bool $skipIfNoIntl)
    {
        if ($skipIfNoIntl && !Craft::$app->getI18n()->getIsIntlLoaded()) {
            $this->markTestSkipped('Need the Intl extension to test this function.');
        }

        self::assertSame($expected, Localization::normalizeLanguage($language));
    }

    /**
     *
     */
    public function testLanguageNormalizationExceptions()
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
     *
     * @param mixed $expected
     * @param mixed $number
     * @param string|null $localeId
     */
    public function testNormalizeNumber($expected, $number, ?string $localeId)
    {
        self::assertSame($expected, Localization::normalizeNumber($number, $localeId));
    }

    /**
     * @dataProvider localeDataDataProvider
     *
     * @param array|null $expected
     * @param string $localeId
     */
    public function testLocaleData(?array $expected, string $localeId)
    {
        self::assertSame($expected, Localization::localeData($localeId));
    }

    /**
     * @return array
     */
    public function normalizeLanguageDataProvider(): array
    {
        return [
            ['nl', 'nl', false],
            ['en-US', 'en-US', false],
            ['af', 'af', true],
            ['af-NA', 'af-NA', true],
            ['en-AG', 'en-ag', true],
            ['en-AG', 'EN-AG', true],
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

    /**
     * @return array
     */
    public function localeDataDataProvider(): array
    {
        $dir = dirname(__DIR__, 3) . '/src/config/locales/nl.php';
        $nlTranslation = require $dir;

        return [
            [
                [
                    'english' => 'language',
                    'spanish' => 'language',
                    'french' => [
                        'language', 'france'
                    ]
                ], 'a-locale-id'
            ],
            [['language2'], '/sub/another-locale-id'],
            [ArrayHelper::merge($nlTranslation, ['dutch' => 'a language']), 'nl']
        ];
    }
}
