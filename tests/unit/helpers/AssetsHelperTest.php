<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Codeception\Test\Unit;
use Craft;
use craft\elements\Asset;
use craft\helpers\Assets;
use crafttests\fixtures\AssetsFixture;
use UnitTester;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * Class AssetsHelper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AssetsHelperTest extends Unit
{
    // Public Properties
    // =========================================================================

    /**
     * @var UnitTester
     */
    protected $tester;

    // Public Methods
    // =========================================================================

    public function _fixtures(): array
    {
        return [
            'assets' => [
                'class' => AssetsFixture::class
            ]
        ];
    }

    // Tests
    // =========================================================================

    /**
     * @dataProvider urlGenerationDataProvider
     *
     * @param $resultUrl
     * @param $params
     * @throws InvalidConfigException
     */
    public function testUrlGeneration($resultUrl, $params)
    {
        $assetQuery = Asset::find();

        foreach ($params as $key => $value) {
            $assetQuery->$key = $value;
        }

        $asset = $assetQuery->one();
        $volume = $asset->getVolume();

        $this->assertSame($resultUrl, Assets::generateUrl($volume, $asset));
    }

    /**
     * @throws Exception
     */
    public function testTempFilePath()
    {
        $tempPath = Assets::tempFilePath();
        $this->assertNotFalse(strpos($tempPath, '' . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'temp'));
        $tempPath = Assets::tempFilePath('test');
        $this->assertNotFalse(strpos($tempPath, '.test'));
    }

    /**
     * @dataProvider prepareAssetNameDataProvider
     *
     * @param $result
     * @param $name
     * @param $isFilename
     * @param $preventPluginMods
     */
    public function testPrepareAssetName($result, $name, $isFilename, $preventPluginMods)
    {
        $assetName = Assets::prepareAssetName($name, $isFilename, $preventPluginMods);
        $this->assertSame($result, $assetName);
    }

    /**
     *
     */
    public function testPrepareAssetNameAsciiRemove()
    {
        Craft::$app->getConfig()->getGeneral()->convertFilenamesToAscii = true;
        $this->assertSame('test.text', Assets::prepareAssetName('tes§t.text'));
    }

    /**
     *
     */
    public function testConfigSeparator()
    {
        Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = '||';
        $this->assertSame('te||st.notafile', Assets::prepareAssetName('te st.notafile'));

        Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = [];
        $this->assertSame('t est.notafile', Assets::prepareAssetName('t est.notafile'));

        Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = 123;
        $this->assertSame('t est.notafile', Assets::prepareAssetName('t est.notafile'));
    }

    /**
     * @dataProvider filename2TitleDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testFilename2Title($result, $input)
    {
        $file2Title = Assets::filename2Title($input);
        $this->assertSame($result, $file2Title);
    }

    /**
     * @dataProvider fileKindLabelDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testFileFindLabel($result, $input)
    {
        $label = Assets::getFileKindLabel($input);
        $this->assertSame($result, $label);
    }

    /**
     * @dataProvider fileKindByExtensionDataProvider
     *
     * @param $result
     * @param $input
     */
    public function testFileKindByExtension($result, $input)
    {
        $kind = Assets::getFileKindByExtension($input);
        $this->assertSame($result, $kind);
    }

    /**
     * @dataProvider parseFileLocationDataProvider
     *
     * @param $result
     * @param $input
     *
     * @throws Exception
     */
    public function testParseFileLocation($result, $input)
    {
        $location = Assets::parseFileLocation($input);
        $this->assertSame($result, $location);
    }

    /**
     *
     */
    public function testParseFileLocationException()
    {
        $this->tester->expectThrowable(Exception::class, function() {
            Assets::parseFileLocation('!@#$%^&*()_');
        });
        $this->tester->expectThrowable(Exception::class, function() {
            Assets::parseFileLocation('');
        });
        $this->tester->expectThrowable(Exception::class, function() {
            Assets::parseFileLocation('{folder:string}.');
        });
    }

    /**
     *
     */
    public function testMaxUploadSize()
    {
        Craft::$app->getConfig()->getGeneral()->maxUploadFileSize = 1;
        $this->assertSame(1, Assets::getMaxUploadSize());
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function urlGenerationDataProvider(): array
    {
        return [
            ['https://cdn.test.craftcms.test/test-volume-1/product.jpg', ['volumeId' => '1000', 'filename' => 'product.jpg']]
        ];
    }

    /**
     * @return array
     */
    public function prepareAssetNameDataProvider(): array
    {
        return [
            ['name.', 'name', true, false],
            ['NAME.', 'NAME', true, false],

            ['te-@st.notaf ile', 'te !@#$%^&*()st.notaf ile', true, false],
            ['', '', false, false],
            ['-.', '', true, false],
        ];
    }

    /**
     * @return array
     */
    public function filename2TitleDataProvider(): array
    {
        return [
            ['Filename', 'filename'],
            ['File name is with chars', 'file.name_is-with chars'],
            ['File name is with chars', 'file.name_is-with chars!@#$%^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function fileKindLabelDataProvider(): array
    {
        return [
            ['Access', 'access'],
            ['Audio', 'audio'],
            ['Text', 'text'],
            ['PHP', 'php'],
            ['unknown', 'Raaa']
        ];
    }

    /**
     * @return array
     */
    public function parseFileLocationDataProvider(): array
    {
        return [
            [['2', '.'], '{folder:2}.'],
            [['2', '.!@#$%^&*()'], '{folder:2}.!@#$%^&*()']
        ];
    }

    /**
     * @return array
     */
    public function fileKindByExtensionDataProvider(): array
    {
        return [
            ['unknown', 'html'],
            ['access', 'file.accdb'],
        ];
    }
}
