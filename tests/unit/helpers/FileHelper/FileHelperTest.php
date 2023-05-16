<?php
/**
 * @link      https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license   https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers\FileHelper;

use Codeception\Test\Unit;
use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
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
    /**
     * @var UnitTester
     */
    protected $tester;

    /**
     * @throws ErrorException
     * @throws Exception
     */
    public function testCreateRemove()
    {
        $location = dirname(__DIR__, 4) . '/at-root';
        FileHelper::createDirectory('at-root');
        self::assertDirectoryExists($location);

        FileHelper::removeDirectory($location);
        self::assertDirectoryNotExists($location);

        self::assertNull(FileHelper::removeDirectory('notadir'));
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
        self::assertTrue(FileHelper::isDirectoryEmpty($copyIntoDir));

        // Test that clearing an empty dir wont make things go wrong.
        FileHelper::clearDirectory($copyIntoDir);

        // Copy into the directory
        FileHelper::copyDirectory($copyFromDir, $copyIntoDir);

        // Make sure something exists
        self::assertSame(scandir($copyFromDir, 1), scandir($copyIntoDir, 1));
        self::assertFalse(FileHelper::isDirectoryEmpty($copyIntoDir));

        // Clear it out.
        FileHelper::clearDirectory($copyIntoDir);

        // Ensure everything is empty.
        self::assertTrue(FileHelper::isDirectoryEmpty($copyIntoDir));
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
     * @dataProvider normalizePathDataProvider
     *
     * @param string $expected
     * @param string $path
     * @param string $ds
     */
    public function testNormalizePath(string $expected, string $path, string $ds)
    {
        self::assertSame($expected, FileHelper::normalizePath($path, $ds));
    }

    /**
     * @dataProvider isDirectoryEmptyDataProvider
     *
     * @param bool $expected
     * @param string $dir
     * @throws ErrorException
     */
    public function testIsDirectoryEmpty(bool $expected, string $dir)
    {
        self::assertSame($expected, FileHelper::isDirectoryEmpty($dir));
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
     * @dataProvider mimeTypeDataProvider
     *
     * @param string|null $expected
     * @param string $file
     * @param string|null $magicFile
     * @param bool $checkExtension
     * @throws InvalidConfigException
     */
    public function testGetMimeType(?string $expected, string $file, ?string $magicFile, bool $checkExtension)
    {
        self::assertSame($expected, FileHelper::getMimeType($file, $magicFile, $checkExtension));
    }

    /**
     *
     */
    public function testGetMimeTypeExceptions()
    {
        if (PHP_VERSION_ID < 80100) {
            $this->tester->expectThrowable(ErrorException::class, function() {
                FileHelper::getMimeType('notafile');
            });
        }
    }

    /**
     * @dataProvider getExtensionByMimeTypeDataProvider
     *
     * @param string $expected
     * @param string $mimeType
     */
    public function testGetExtensionByMimeType(string $expected, string $mimeType)
    {
        self::assertSame($expected, FileHelper::getExtensionByMimeType($mimeType));
    }

    /**
     * @dataProvider sanitizeFilenameDataProvider
     *
     * @param string $expected
     * @param string $filename
     * @param array $options
     */
    public function testSanitizeFilename(string $expected, string $filename, array $options)
    {
        self::assertSame($expected, FileHelper::sanitizeFilename($filename, $options));
    }

    /**
     * @dataProvider isSvgDataProvider
     *
     * @param bool $expected
     * @param string $file
     * @param string|null $magicFile
     * @param bool $checkExtension
     */
    public function testIsSvg(bool $expected, string $file, ?string $magicFile, bool $checkExtension)
    {
        self::assertSame($expected, FileHelper::isSvg($file, $magicFile, $checkExtension));
    }

    /**
     * @dataProvider isGifDataProvider
     *
     * @param bool $expected
     * @param string $input
     * @param string|null $magicFile
     * @param bool $checkExtension
     */
    public function testIsGif(bool $expected, string $input, ?string $magicFile, bool $checkExtension)
    {
        self::assertSame($expected, FileHelper::isGif($input, $magicFile, $checkExtension));
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

        self::assertTrue(is_file($file));
        self::assertSame($content, file_get_contents($file));

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
        self::assertSame('contents', file_get_contents($file));

        FileHelper::writeToFile($file, 'changed');
        self::assertSame('changed', file_get_contents($file));

        FileHelper::writeToFile($file, 'andappended', ['append' => true]);
        self::assertSame('changedandappended', file_get_contents($file));

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

    /**
     * @dataProvider uniqueNameDataProvider
     *
     * @param string $expectedPattern
     * @param string $baseName
     */
    public function testUniqueName(string $expectedPattern, string $baseName)
    {
        $expectedPattern = str_replace('{id}', '[\w\.]{23}', $expectedPattern);
        self::assertRegExp("/^$expectedPattern$/", FileHelper::uniqueName($baseName));
    }

    /**
     * @return array
     */
    public function normalizePathDataProvider(): array
    {
        return [
            ['Im a string', 'Im a string', DIRECTORY_SEPARATOR],
            [
                'c:' . DIRECTORY_SEPARATOR . 'vagrant' . DIRECTORY_SEPARATOR . 'box',
                'c:/vagrant/box',
                DIRECTORY_SEPARATOR,
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
    public function mimeTypeDataProvider(): array
    {
        return [
            ['application/pdf', dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            ['text/html', dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, true],
            ['image/gif', dirname(__DIR__, 3) . '/_data/assets/files/example-gif.gif', null, true],
            ['application/pdf', dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            ['image/svg+xml', dirname(__DIR__, 3) . '/_data/assets/files/gng.svg', null, true],
            ['application/xml', dirname(__DIR__, 3) . '/_data/assets/files/random.xml', null, true],
            [PHP_VERSION_ID >= 80100 ? 'text/html' : 'text/plain', dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, false],
            [PHP_VERSION_ID >= 80100 ? null : 'directory', __DIR__, null, true],
        ];
    }

    /**
     * @return array
     */
    public function isSvgDataProvider(): array
    {
        return [
            [true, dirname(__DIR__, 3) . '/_data/assets/files/gng.svg', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/empty-file.text', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/example-gif.gif', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/random.xml', null, true],
            [false, __DIR__, null, true],
        ];
    }

    /**
     * @return array
     */
    public function isGifDataProvider(): array
    {
        return [
            [true, dirname(__DIR__, 3) . '/_data/assets/files/example-gif.gif', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/empty-file.text', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/gng.svg', null, true],
            [false, dirname(__DIR__, 3) . '/_data/assets/files/random.xml', null, true],
            [false, __DIR__, null, true],
        ];
    }

    /**
     * @return array
     */
    public function isDirectoryEmptyDataProvider(): array
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
    public function getExtensionByMimeTypeDataProvider(): array
    {
        return [
            ['jpg', 'image/jpeg'],
            ['svg', 'image/svg+xml'],
        ];
    }

    /**
     * @return array
     */
    public function sanitizeFilenameDataProvider(): array
    {
        return [
            ['notafile', 'notafile', []],
            ['not-a-file', 'not a file', []],
            ['im-a-file@.svg', 'im-a-file!@#$%^&*(.svg', []],
            ['iPS(c)m-a-file.svg', 'i£©m-a-file⚽🐧🎺.svg', ['asciiOnly' => true]],
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

    /**
     * @return array
     */
    public function uniqueNameDataProvider(): array
    {
        $bigStr = StringHelper::randomString(300);

        return [
            ['{id}', ''],
            ['foo{id}', 'foo'],
            ['{id}.ext', '.ext'],
            ['foo{id}.ext', 'foo.ext'],
            [sprintf('%s{id}.ext', substr($bigStr, 0, 228)), "$bigStr.ext"],
        ];
    }

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
