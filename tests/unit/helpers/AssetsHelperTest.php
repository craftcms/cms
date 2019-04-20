<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\db\Query;
use craft\elements\Asset;
use craft\helpers\Assets;
use craft\helpers\ConfigHelper;
use craftunit\fixtures\AssetsFixture;
use craftunit\fixtures\VolumesFolderFixture;
use craftunit\fixtures\VolumesFixture;
use yii\base\Exception;

/**
 * Class AssetsHelper.
 *
s * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.0
 */
class AssetsHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;


    public function _fixtures()
    {
        return [
            'assets' => [
                'class' => AssetsFixture::class
            ]
        ];
    }

    /**
     * @param $result
     * @param $input
     * @dataProvider urlGenerationData
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

    public function urlGenerationData()
    {
        return [
            ['https://cdn.test.craftcms.dev/test-volume-1/product.jpg', ['volumeId' => '1000', 'filename' => 'product.jpg']]
        ];
    }


    /**
     * @param $result
     * @param $input
     *
     * @throws \yii\base\Exception
     */
    public function testTempFilePath()
    {
        $tempPath = Assets::tempFilePath();
        $this->assertNotFalse(strpos($tempPath, '\_craft\storage\runtime\temp'));
        $tempPath = Assets::tempFilePath('test');
        $this->assertNotFalse(strpos($tempPath, '.test'));
    }

    /**
     * @dataProvider prepareAssetNameData
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

    public function prepareAssetNameData()
    {
        return [
            ['name.', 'name', true, false],
            ['NAME.', 'NAME', true, false],

            ['te-@st.notaf ile', 'te !@#$%^&*()st.notaf ile', true, false],
            ['', '', false, false],
            ['-.', '', true, false],
        ];
    }

    public function testPrepareAssetNameAsciiRemove()
    {
        \Craft::$app->getConfig()->getGeneral()->convertFilenamesToAscii = true;
        $this->assertSame('test.text', Assets::prepareAssetName('tes§t.text'));
    }

    public function testConfigSeperator()
    {
        \Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = '||';
        $this->assertSame('te||st.notafile', Assets::prepareAssetName('te st.notafile'));

        \Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = [];
        $this->assertSame('t est.notafile', Assets::prepareAssetName('t est.notafile'));

        \Craft::$app->getConfig()->getGeneral()->filenameWordSeparator = 123;
        $this->assertSame('t est.notafile', Assets::prepareAssetName('t est.notafile'));
    }

    /**
     * @dataProvider filename2TitleData
     *
     * @param $result
     * @param $input
     */
    public function testFilename2Title($result, $input)
    {
        $file2Title = Assets::filename2Title($input);
        $this->assertSame($result, $file2Title);
    }

    public function filename2TitleData()
    {
        return [
            ['Filename', 'filename'],
            ['File Name Is With Chars', 'file.name_is-with chars'],
            ['File Name Is With Chars!@#$%^&*()', 'file.name_is-with chars!@#$%^&*()'],
        ];
    }

    /**
     * @dataProvider fileKindLabelData
     *
     * @param $result
     * @param $input
     */
    public function testFileFindLabel($result, $input)
    {
        $label = Assets::getFileKindLabel($input);
        $this->assertSame($result, $label);
    }

    public function fileKindLabelData()
    {
        $returnArray = [];
        foreach (Assets::getFileKinds() as $key => $fileKind) {
            $returnArray[] = [$fileKind['label'], $key];
        }

        $returnArray[] = ['unknown', 'not a file'];
        return $returnArray;
    }

    /**
     * @dataProvider fileKindByExtensionData
     *
     * @param $result
     * @param $input
     */
    public function testFileKindByExtension($result, $input)
    {
        $kind = Assets::getFileKindByExtension($input);
        $this->assertSame($result, $kind);
    }

    public function fileKindByExtensionData()
    {
        return [
            ['unknown', 'html'],
            ['access', 'file.accdb'],
        ];
    }

    /**
     * @dataProvider parseFileLocationData
     * @param $result
     * @param $input
     *
     * @throws \yii\base\Exception
     */
    public function testParseFileLocation($result, $input)
    {
        $location = Assets::parseFileLocation($input);
        $this->assertSame($result, $location);
    }
    public function parseFileLocationData()
    {
        return [
            [['2', '.'], '{folder:2}.'],
            [['2', '.!@#$%^&*()'], '{folder:2}.!@#$%^&*()']
        ];
    }
    public function testParseFileLocationException()
    {
        $this->tester->expectThrowable(Exception::class, function (){
            Assets::parseFileLocation('!@#$%^&*()_');
        });
        $this->tester->expectThrowable(Exception::class, function (){
            Assets::parseFileLocation('');
        });
        $this->tester->expectThrowable(Exception::class, function (){
            Assets::parseFileLocation('{folder:string}.');
        });
    }

    public function testMaxUploadSize()
    {
        $oldMaxFile = ini_get('upload_max_filesize');
        $oldMaxPost = ini_get('post_max_size');
        $memLimit = ini_get('memory_limit');

        $this->setUploadIniValues();
        $this->assertSame(16777216, Assets::getMaxUploadSize());

        ini_set('memory_limit', '0M');
        $this->assertSame(16777216, Assets::getMaxUploadSize());

        $this->setUploadIniValues();
        \Craft::$app->getConfig()->getGeneral()->maxUploadFileSize = 1;
        $this->assertSame(1, Assets::getMaxUploadSize());

        ini_set('upload_max_filesize', $oldMaxFile);
        ini_set('post_max_size', $oldMaxPost);
        ini_set('memory_limit', $memLimit);
    }

    private function setUploadIniValues()
    {
        ini_set('upload_max_filesize', '500M');
        ini_set('post_max_size', '500M');
        ini_set('memory_limit', '16M');
    }
}