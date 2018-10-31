<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */


namespace craftunit\helpers;


use Codeception\Test\Unit;
use craft\elements\Asset;
use craft\helpers\Assets;
use craftunit\fixtures\AssetsFixture;
use craftunit\fixtures\VolumesFolderFixture;
use craftunit\fixtures\VolumesFixture;

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
    /**
     * TODO: When saving via active record ids arent stored onto the record and are thus not usable
     * [yii\db\IntegrityException] SQLSTATE[23000]: Integrity constraint violation: 1048 Column 'elementId' cannot be null
    The SQL being executed was: INSERT INTO `craft_elements_sites` (`elementId`, `siteId`, `slug`, `uri`, `enabled`, `dateCreated`, `uid`, `dateUpdated`) VALUES (NULL, 1, NULL, NULL, 1, '2018-10-31 20:47:36', '18e780f5-54e8-47eb-9d0b-a7abbf6538e9', '2018-10-31 20:47:36')


    public function _fixtures()
    {
        return [
            'volumes' => [
                'class' => VolumesFixture::class,
            ],
            'volumes-folder' => [
                'class' => VolumesFolderFixture::class,
            ],
            'assets' => [
                'class' => AssetsFixture::class
            ]
        ];
    }
     * */

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
        return [

        ];
    }
}