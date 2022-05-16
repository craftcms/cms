<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers;

use Craft;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\test\TestCase;
use crafttests\fixtures\AssetFixture;
use UnitTester;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Class AssetsHelper.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class AssetsHelperTest extends TestCase
{
    /**
     * @var UnitTester
     */
    protected UnitTester $tester;

    public function _fixtures(): array
    {
        return [
            'assets' => [
                'class' => AssetFixture::class,
            ],
        ];
    }

    /**
     * @dataProvider generateUrlDataProvider
     * @param string $expected
     * @param array $params
     * @throws InvalidConfigException
     */
    public function testGenerateUrl(string $expected, array $params): void
    {
        $assetQuery = Asset::find();

        foreach ($params as $key => $value) {
            $assetQuery->$key = $value;
        }

        /** @var Asset|null $asset */
        $asset = $assetQuery->one();
        $fs = $asset->getFs();

        self::assertSame($expected, Assets::generateUrl($fs, $asset));
    }

    /**
     * @throws Exception
     */
    public function testTempFilePath(): void
    {
        $tempPath = Assets::tempFilePath();
        self::assertNotFalse(strpos($tempPath, '' . DIRECTORY_SEPARATOR . '_craft' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'runtime' . DIRECTORY_SEPARATOR . 'temp'));
        $tempPath = Assets::tempFilePath('test');
        self::assertNotFalse(strpos($tempPath, '.test'));
    }

    /**
     * @dataProvider prepareAssetNameDataProvider
     * @param string $expected
     * @param string $name
     * @param bool $isFilename
     * @param bool $preventPluginModifications
     */
    public function testPrepareAssetName(string $expected, string $name, bool $isFilename, bool $preventPluginModifications): void
    {
        self::assertSame($expected, Assets::prepareAssetName($name, $isFilename, $preventPluginModifications));
    }

    /**
     *
     */
    public function testPrepareAssetNameAsciiRemove(): void
    {
        Craft::$app->getConfig()->getGeneral()->convertFilenamesToAscii = true;
        self::assertSame('tesSSt.text', Assets::prepareAssetName('tes§t.text'));
    }

    /**
     *
     */
    public function testConfigSeparator(): void
    {
        Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = '||';
        self::assertSame('te||st.notafile', Assets::prepareAssetName('te st.notafile'));

        Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = false;
        self::assertSame('t est.notafile', Assets::prepareAssetName('t est.notafile'));
    }

    /**
     * @dataProvider filename2TitleDataProvider
     * @param string $expected
     * @param string $filename
     */
    public function testFilename2Title(string $expected, string $filename): void
    {
        self::assertSame($expected, Assets::filename2Title($filename));
    }

    /**
     * @dataProvider getFileKindLabelDataProvider
     * @param string $expected
     * @param string $kind
     */
    public function testGetFileKindLabel(string $expected, string $kind): void
    {
        self::assertSame($expected, Assets::getFileKindLabel($kind));
    }

    /**
     * @dataProvider getFileKindByExtensionDataProvider
     * @param string $expected
     * @param string $file
     */
    public function testGetFileKindByExtension(string $expected, string $file): void
    {
        self::assertSame($expected, Assets::getFileKindByExtension($file));
    }

    /**
     * @dataProvider parseFileLocationDataProvider
     * @param array $expected
     * @param string $location
     * @throws Exception
     */
    public function testParseFileLocation(array $expected, string $location): void
    {
        self::assertSame($expected, Assets::parseFileLocation($location));
    }

    /**
     *
     */
    public function testParseFileLocationException(): void
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
    public function testMaxUploadSize(): void
    {
        Craft::$app->getConfig()->getGeneral()->maxUploadFileSize = 1;
        self::assertSame(1, Assets::getMaxUploadSize());
    }

    /**
     * @dataProvider parseSrcsetSizeDataProvider
     * @param array|false $expected
     * @param mixed $size
     */
    public function testParseSrcsetSize(array|false $expected, mixed $size): void
    {
        if (is_array($expected)) {
            self::assertSame($expected, Assets::parseSrcsetSize($size));
        } else {
            $this->tester->expectThrowable(InvalidArgumentException::class, function() use ($size) {
                Assets::parseSrcsetSize($size);
            });
        }
    }

    /**
     * @return array
     */
    public function generateUrlDataProvider(): array
    {
        return [
            ['https://cdn.test.craftcms.test/test%20volume%201/product.jpg', ['volumeId' => '1000', 'filename' => 'product.jpg']],
        ];
    }

    /**
     * @return array
     */
    public function prepareAssetNameDataProvider(): array
    {
        return [
            ['name', 'name', true, false],
            ['NAME', 'NAME', true, false],

            ['name', 'name.', true, false],

            ['te-@st.notaf ile', 'te !@#$%^&*()st.notaf ile', true, false],
            ['', '', false, false],
            ['-', '', true, false],

            // Make sure the filenames are getting cut down to 255 chars
            [str_repeat('o', 251) . '.jpg', str_repeat('o', 252) . '.jpg', true, false],
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
    public function getFileKindLabelDataProvider(): array
    {
        return [
            ['Access', 'access'],
            ['Audio', 'audio'],
            ['Text', 'text'],
            ['PHP', 'php'],
            ['unknown', 'Raaa'],
        ];
    }

    /**
     * @return array
     */
    public function parseFileLocationDataProvider(): array
    {
        return [
            [[2, '.'], '{folder:2}.'],
            [[2, '.!@#$%^&*()'], '{folder:2}.!@#$%^&*()'],
        ];
    }

    /**
     * @return array
     */
    public function parseSrcsetSizeDataProvider(): array
    {
        return [
            [[100.0, 'w'], 100],
            [[100.0, 'w'], '100w'],
            [[100.0, 'w'], '100W'],
            [[2.0, 'x'], '2x'],
            [[2.0, 'x'], '2X'],
            [false, '2xo'],
        ];
    }

    /**
     * @return array
     */
    public function getFileKindByExtensionDataProvider(): array
    {
        return [
            ['unknown', 'html'],
            ['access', 'file.accdb'],
        ];
    }
}
