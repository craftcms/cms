<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers\filehelper;

use Codeception\Test\Unit;
use craft\helpers\FileHelper;
use UnitTester;
use yii\base\ErrorException;
use yii\base\Exception;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;

/**
 * Class FileHelperTest.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @author Global Network Group | Giel Tettelaar <giel@yellowflash.net>
 * @since 3.2
 */
class FileHelperTest extends Unit
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
     * @throws ErrorException
     * @throws Exception
     */
    public function testCreateRemove()
    {
        $location = dirname(__DIR__, 4) . '/at-root';
        FileHelper::createDirectory('at-root');
        $this->assertDirectoryExists($location);

        FileHelper::removeDirectory($location);
        $this->assertDirectoryNotExists($location);

        $this->assertNull(FileHelper::removeDirectory('notadir'));
    }

    /**
     * @throws ErrorException
     */
    public function testCopyAndClear()
    {
        $copyIntoDir = __DIR__ . '/sandbox/copyInto';
        $copyFromDir = dirname(__DIR__, 3) . '/_data/assets/files';

        // Clear it.
        FileHelper::clearDirectory($copyIntoDir);

        // Make sure its clear
        $this->assertTrue(FileHelper::isDirectoryEmpty($copyIntoDir));

        // Test that clearing an empty dir wont make things go wrong.
        FileHelper::clearDirectory($copyIntoDir);

        // Copy into the directory
        FileHelper::copyDirectory($copyFromDir, $copyIntoDir);

        // Make sure something exists
        $this->assertSame(scandir($copyFromDir, 1), scandir($copyIntoDir, 1));
        $this->assertFalse(FileHelper::isDirectoryEmpty($copyIntoDir));

        // Clear it out.
        FileHelper::clearDirectory($copyIntoDir);

        // Ensure everything is empty.
        $this->assertTrue(FileHelper::isDirectoryEmpty($copyIntoDir));
    }

    /**
     *
     */
    public function testClearException()
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            FileHelper::clearDirectory('not-a-dir');
        });
    }

    /**
     * @dataProvider pathNormalizedDataProvider
     *
     * @param $result
     * @param $path
     * @param $dirSeparator
     */
    public function testPathNormalization($result, $path, $dirSeparator)
    {
        $normalized = FileHelper::normalizePath($path, $dirSeparator);
        $this->assertSame($result, $normalized);
    }

    /**
     * @dataProvider isDirEmptyDataProvider
     *
     * @param $result
     * @param $input
     * @throws ErrorException
     */
    public function testIsDirEmpty($result, $input)
    {
        $isEmpty = FileHelper::isDirectoryEmpty($input);
        $this->assertSame($result, $isEmpty);
    }

    /**
     *
     */
    public function testIsDirEmptyExceptions()
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            FileHelper::isDirectoryEmpty('aaaaa//notadir');
        });
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            FileHelper::isDirectoryEmpty(__DIR__ . '/sandbox/isdirempty/dotfile/no/test');
        });
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
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

    /**
     * @dataProvider mimeTypeDataProvider
     *
     * @param $file
     * @param $magicFile
     * @param $checkExtension
     * @param $actualMimeType
     * @throws InvalidConfigException
     */
    public function testGetMimeType($file, $magicFile, $checkExtension, $actualMimeType)
    {
        $mimeType = FileHelper::getMimeType($file, $magicFile, $checkExtension);
        $this->assertSame($actualMimeType, $mimeType);
    }

    /**
     * @dataProvider mimeTypeFalseDataProvider
     *
     * @param $result
     * @param $file
     *
     * @throws InvalidConfigException
     */
    public function testGetMimeTypeOnFalse($result, $file)
    {
        $mimeType = FileHelper::getMimeType($file, null, false);
        $this->assertSame($result, $mimeType);
    }

    /**
     *
     */
    public function testGetMimeTypeExceptions()
    {
        $this->tester->expectThrowable(ErrorException::class, function() {
            FileHelper::getMimeType('notafile');
        });
    }

    /**
     * @dataProvider sanitizedFilenameDataProvider
     *
     * @param $result
     * @param $input
     * @param $options
     */
    public function testFilenameSanitation($result, $input, $options)
    {
        $sanitized = FileHelper::sanitizeFilename($input, $options);
        $this->assertSame($result, $sanitized);
    }

    /**
     * @dataProvider mimeTypeDataProvider
     *
     * @param $input
     * @param $magicFile
     * @param $checkExtension
     */
    public function testIsSvg($input, $magicFile, $checkExtension)
    {
        $result = false;
        if (strpos($input, '.svg') !== false) {
            $result = true;
        }

        $isSvg = FileHelper::isSvg($input, $magicFile, $checkExtension);
        $this->assertSame($result, $isSvg);
    }

    /**
     * @dataProvider mimeTypeDataProvider
     *
     * @param $input
     * @param $magicFile
     * @param $checkExtension
     */
    public function testIsGif($input, $magicFile, $checkExtension)
    {
        $result = false;
        if (strpos($input, '.gif') !== false) {
            $result = true;
        }

        $isSvg = FileHelper::isGif($input, $magicFile, $checkExtension);
        $this->assertSame($result, $isSvg);
    }

    /**
     * @dataProvider writeToFileDataProvider
     *
     * @param $content
     * @param $file
     * @param $contents
     * @param $options
     * @param bool $removeDir
     * @param string $removeableDir
     * @throws ErrorException
     */
    public function testWriteToFile($content, $file, $contents, $options, $removeDir = false, $removeableDir = '')
    {
        FileHelper::writeToFile($file, $contents, $options);

        $this->assertTrue(is_file($file));
        $this->assertSame($content, file_get_contents($file));

        if ($removeDir) {
            FileHelper::removeDirectory($removeableDir);
        } else {
            FileHelper::unlink($file);
        }
    }

    /**
     * @throws ErrorException
     */
    public function testWriteToFileAppend()
    {
        $sandboxDir = __DIR__ . '/sandbox/writeto';
        $file = $sandboxDir . '/test-file';

        FileHelper::writeToFile($file, 'contents');
        $this->assertSame('contents', file_get_contents($file));

        FileHelper::writeToFile($file, 'changed');
        $this->assertSame('changed', file_get_contents($file));

        FileHelper::writeToFile($file, 'andappended', ['append' => true]);
        $this->assertSame('changedandappended', file_get_contents($file));

        FileHelper::unlink($file);
    }

    /**
     *
     */
    public function testWriteToFileExceptions()
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            FileHelper::writeToFile('notafile/folder', 'somecontent', ['createDirs' => false]);
        });
    }

    // Data Providers
    // =========================================================================

    /**
     * @return array
     */
    public function pathNormalizedDataProvider(): array
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
     * @return array
     */
    public function isWritableDataProvider(): array
    {
        return [
            [true, __DIR__ . '/sandbox/iswritable/dir'],
            [true, __DIR__ . '/sandbox/iswritable/dirwithfile'],
            [true, __DIR__ . '/sandbox/iswritable/dirwithfile/test.text'],
            [true, 'i/dont/exist/as/a/dir/'],
        ];
    }

    /**
     * @return array
     */
    public function mimeTypeDataProvider(): array
    {
        return [
            [dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true, 'application/pdf'],
            [dirname(__DIR__, 3) . '/_data/assets/files/empty-file.text', null, true, 'inode/x-empty'],
            [dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, true, 'text/html'],
            [dirname(__DIR__, 3) . '/_data/assets/files/example-gif.gif', null, true, 'image/gif'],
            [dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true, 'application/pdf'],
            [dirname(__DIR__, 3) . '/_data/assets/files/gng.svg', null, true, 'image/svg+xml'],
            [dirname(__DIR__, 3) . '/_data/assets/files/random.xml', null, true, 'application/xml'],
            [__DIR__, null, true, 'directory'],
        ];
    }

    /**
     * @return array
     */
    public function mimeTypeFalseDataProvider(): array
    {
        return [
            ['text/plain', dirname(__DIR__, 3) . '/_data/assets/files/test.html'],

        ];
    }

    /**
     * @return array
     */
    public function isDirEmptyDataProvider(): array
    {
        return [
            [true, __DIR__ . '/sandbox/isdirempty/yes'],
            [false, __DIR__ . '/sandbox/isdirempty/no'],
            [false, __DIR__ . '/sandbox/isdirempty/dotfile'],
        ];
    }

    /**
     * @return array
     */
    public function sanitizedFilenameDataProvider(): array
    {
        return [
            ['notafile', 'notafile', []],
            ['not-a-file', 'not a file', []],
            ['im-a-file@.svg', 'im-a-file!@#$%^&*(.svg', []],
            ['i(c)m-a-file.svg', 'i£©m-a-file⚽🐧🎺.svg', ['asciiOnly' => true]],
            ['not||a||file', 'not a file', ['separator' => '||']],
            ['not🐧a🐧file', 'not a file', ['separator' => '🐧', 'asciiOnly' => true]],
        ];
    }

    /**
     * @return array
     */
    public function writeToFileDataProvider(): array
    {
        $sandboxDir = __DIR__ . '/sandbox/writeto';

        return [
            ['content', $sandboxDir . '/notafile', 'content', []],
            ['content', $sandboxDir . '/notadir/notafile', 'content', [], true, $sandboxDir . '/notadir'],
            ['content', $sandboxDir . '/notafile2', 'content', ['lock' => true]],

        ];
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function _before()
    {
        if (!is_dir(__DIR__ . '/sandbox/copyInto')) {
            FileHelper::createDirectory(__DIR__ . '/sandbox/copyInto');
        }

        FileHelper::clearDirectory(__DIR__ . '/sandbox/copyInto');

        if (!is_dir(__DIR__ . '/sandbox/isdirempty/yes')) {
            FileHelper::createDirectory(__DIR__ . '/sandbox/isdirempty/yes');
        }

        FileHelper::clearDirectory(__DIR__ . '/sandbox/isdirempty/yes');
    }
}
