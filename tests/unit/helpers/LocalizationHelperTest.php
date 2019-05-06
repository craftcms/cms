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
use function dirname;
use UnitTester;
use yii\base\InvalidArgumentException;
use yii\i18n\MissingTranslationEvent;

/**
 * Class LocalizationHelper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class LocalizationHelperTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;


    /**
     * @dataProvider languageNormalizationData
     *
     * @param $result
     * @param $input
     */
    public function testLanguageNormalization($result, $input)
    {
        $normalized = Localization::normalizeLanguage($input);
        $this->assertSame($result, $normalized);
    }

    public function languageNormalizationData()
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

    public function testLanugageNormalizationExceptions()
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function () {
            Localization::normalizeLanguage('dutch');
        });
        $this->tester->expectThrowable(InvalidArgumentException::class, function () {
            Localization::normalizeLanguage('notalang');
        });
    }

    /**
     * @dataProvider numberNormalizationData
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

    public function numberNormalizationData()
    {
        return [
            ['2000000000', '20,0000,0000', null],
            ['20 0000 0000', '20 0000 0000', null],
            ['20.0000.0000', '20.0000.0000', null],
            [2000000000, 2000000000, null],
        ];
    }

    public function testNumberNormalizationCustomLocale()
    {
        $locale = null;
        foreach (Craft::$app->getI18n()->getAllLocaleIds() as $localeId) {
            if ($localeId !== Craft::$app->language) {
                $locale = $localeId;
            }
        }

        $this->assertSame('29999', Localization::normalizeNumber('2,99,99', $locale));
    }
    /**
     * @dataProvider localeDataData
     *
     * @param $result
     * @param $input
     */
    public function testLocaleData($result, $input)
    {
        $data = Localization::localeData($input);
        $this->assertSame($result, $data);
    }

    public function localeDataData()
    {
        $dir = dirname(__DIR__, 3).'/src/config/locales/nl.php';
        $nlTranslation = require_once $dir;

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

    /*
     * @TODO: Fix this method and find a way to alter the PathService $_configPath variable.
     */
    public function testCustomConfigPathDirGetsMerged()
    {
        $this->markTestSkipped();
        $oldConfigPath = Craft::$app->getConfig()->configDir;
        Craft::$app->getConfig()->configDir = dirname(__DIR__, 3).'/_data/assets/files';
        $this->assertSame([], Localization::localeData('a-locale-id'));
        Craft::$app->getConfig()->configDir = $oldConfigPath;
    }

    /**
     * @dataProvider findMissingTranslationData
     *
     * @param $result
     * @param $input
     */
    public function testFindMissingTranslation($result, $input)
    {
        $missing = Localization::findMissingTranslation($input);
        $this->assertSame($result, $missing);
    }

    public function findMissingTranslationData()
    {
        return [
        ];
    }
}