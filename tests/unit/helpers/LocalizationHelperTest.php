<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace craftunit\helpers;

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
 * @since 3.1
 */
class LocalizationHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    // Tests
    // =========================================================================

    /**
     * @dataProvider languageNormalizationDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testLanguageNormalization($result, $input)
    {
        $normalized = Localization::normalizeLanguage($input);
        $this->assertSame($result, $normalized);
    }

    /**
     *
     */
    public function testLanguageNormalizationExceptions()
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function () {
            Localization::normalizeLanguage('dutch');
        });
        $this->tester->expectThrowable(InvalidArgumentException::class, function () {
            Localization::normalizeLanguage('notalang');
        });
    }

    /**
     * @dataProvider numberNormalizationDataProvider
     *
     * @param $result
     * @param $input
     * @param $localeId
     */
    public function testNumberNormalization($result, $input, $localeId)
    {
        $normalization = Localization::normalizeNumber($input, $localeId);
        $this->assertSame($result, $normalization);
    }

    /**
     * @dataProvider localeDataDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testLocaleData($result, $input)
    {
        $data = Localization::localeData($input);
        $this->assertSame($result, $data);
    }

    /**
     * @dataProvider findMissingTranslationDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testFindMissingTranslation($result, $input)
    {
        $this->assertSame($result, Localization::findMissingTranslation($input));
    }

    // Tests
    // =========================================================================

    /**
     * @return array
     */
    public function findMissingTranslationDataProvider(): array
    {
        return [
        ];
    }

    /**
     * @return array
     */
    public function languageNormalizationDataProvider(): array
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
    public function numberNormalizationDataProvider(): array
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
        $dir = dirname(__DIR__, 3).'/src/config/locales/nl.php';
        $nlTranslation = require $dir;

        return [
            [[
                'english' => 'language',
                'spanish' => 'language',
                'french' => [
                    'language', 'france'
                ]
            ], 'a-locale-id'],
            ['language', 'another-locale-id'],
            [['language2'], '/sub/another-locale-id'],
            [ArrayHelper::merge($nlTranslation, ['dutch' => 'a language']), 'nl']
        ];
    }
}
