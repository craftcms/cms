<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */
namespace craftunit\helpers\filehelper;

use Codeception\Test\Unit;
use craft\helpers\FileHelper;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;

/**
 * Class FileHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since  3.0
 */
class FileHelperTest extends Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @dataProvider pathNormalizedData
     *
     * @param $result
     * @param $path
     * @param $dirSeperator
     */
    public function testPathNormalization($result, $path, $dirSeperator)
    {
        $normalized = FileHelper::normalizePath($path, $dirSeperator);
        $this->assertSame($result, $normalized);
    }

    public function pathNormalizedData()
    {
        return [
            ['Im a string', 'Im a string', DIRECTORY_SEPARATOR],
            [
                'c:' . DIRECTORY_SEPARATOR . 'vagrant' . DIRECTORY_SEPARATOR . 'box',
                'c:/vagrant/box',
                DIRECTORY_SEPARATOR
            ],
            ['c:\\vagrant\\box', 'c:/vagrant/box', '\\'],
            ['c:|vagrant|box', 'c:\\vagrant\\box', '|'],
            [' +HostName[@SSL][@Port]+SharedFolder+Resource', ' \\HostName[@SSL][@Port]\SharedFolder\Resource', '+'],
            ['|?|C:|my_dir', '\\?\C:\my_dir', '|'],
            ['==stuff', '\\\\stuff', '='],
        ];
    }

    /**
     * @dataProvider dirCreationData
     *
     * @param $result
     * @param $path
     * @param $mode
     * @param $recursive
     */
    public function testDirCreation($result, $path, $mode, $recursive)
    {

    }

    public function dirCreationData()
    {
        return [

        ];
    }

    /**
     * @dataProvider isDirEmptyData
     *
     * @param $result
     * @param $input
     */
    public function testIsDirEmpty($result, $input)
    {
        $isEmpty = FileHelper::isDirectoryEmpty($input);
        $this->assertSame($result, $isEmpty);
    }

    public function isDirEmptyData()
    {
        return [
            [true, __DIR__ . '/sandbox/isdirempty/yes'],
            [false, __DIR__ . '/sandbox/isdirempty/no'],
            [false, __DIR__ . '/sandbox/isdirempty/dotfile'],
        ];
    }

    public function testIsDirEmptyExceptions()
    {
        $this->tester->expectException(InvalidArgumentException::class, function () {
            FileHelper::isDirectoryEmpty('aaaaa//notadir');
        });
        $this->tester->expectException(InvalidArgumentException::class, function () {
            FileHelper::isDirectoryEmpty(__DIR__ . '/sandbox/isdirempty/dotfile/no/test');
        });
        $this->tester->expectException(InvalidArgumentException::class, function () {
            FileHelper::isDirectoryEmpty('ftp://google.com');
        });
    }

    /**
     * @dataProvider isWritableDataProvider
     *
     * @param $result
     * @param $input
     *
     * @throws ErrorException
     */
    public function testIsWritable($result, $input)
    {
        $isWritable = FileHelper::isWritable($input);
        $this->assertTrue($result, $isWritable);
    }

    public function isWritableDataProvider()
    {
        return [
            [true, __DIR__ . '/sandbox/iswritable/dir'],
            [true, __DIR__ . '/sandbox/iswritable/dirwithfile'],
            [true, __DIR__ . '/sandbox/iswritable/dirwithfile/test.text'],
            [true, 'i/dont/exist/as/a/dir/'],
        ];
    }


    /**
     * @dataProvider mimeTypeData
     * @param $result
     * @param $file
     * @param $magicFile
     * @param $checkExtension
     *
     * @throws \yii\base\InvalidConfigException
     */
    public function testGetMimeType($result, $file, $magicFile, $checkExtension)
    {
        $mimeType = FileHelper::getMimeType();
        $this->assertSame($result, $mimeType);
    }
    public function mimeTypeData()
    {
        return [
            []
        ];
    }

}