<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace crafttests\unit\helpers\FileHelper;

use craft\helpers\FileHelper;
use craft\helpers\StringHelper;
use craft\test\TestCase;
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
class FileHelperTest extends TestCase
{
    protected UnitTester $tester;

    /**
     * @throws ErrorException
     * @throws Exception
     */
    public function testCreateRemove(): void
    {
        $location = dirname(__DIR__, 4) . '/at-root';
        FileHelper::createDirectory('at-root');
        self::assertDirectoryExists($location);

        FileHelper::removeDirectory($location);
        self::assertDirectoryDoesNotExist($location);

        FileHelper::removeDirectory('notadir');
    }

    /**
     * @throws ErrorException
     */
    public function testCopyAndClear(): void
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
    public function testClearException(): void
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            FileHelper::clearDirectory('not-a-dir');
        });
    }

    /**
     * @dataProvider normalizePathDataProvider
     * @param string $expected
     * @param string $path
     * @param string $ds
     */
    public function testNormalizePath(string $expected, string $path, string $ds): void
    {
        self::assertSame($expected, FileHelper::normalizePath($path, $ds));
    }

    /**
     * @dataProvider absolutePathDataProvider
     * @param string $expected
     * @param string $to
     * @param string|null $from
     * @param string $ds
     */
    public function testAbsolutePath(string $expected, string $to, ?string $from, string $ds): void
    {
        self::assertSame($expected, FileHelper::absolutePath($to, $from, $ds));
    }

    /**
     * @dataProvider relativePathDataProvider
     * @param string $expected
     * @param string $to
     * @param string|null $from
     * @param string $ds
     */
    public function testRelativePath(string $expected, string $to, ?string $from, string $ds): void
    {
        self::assertSame($expected, FileHelper::relativePath($to, $from, $ds));
    }

    /**
     * @dataProvider isWithinDataProvider
     * @param bool $expected
     * @param string $path
     * @param string $parentPath
     */
    public function testIsWithin(bool $expected, string $path, string $parentPath): void
    {
        self::assertSame($expected, FileHelper::isWithin($path, $parentPath));
    }

    /**
     * @dataProvider isDirectoryEmptyDataProvider
     * @param bool $expected
     * @param string $dir
     * @throws ErrorException
     */
    public function testIsDirectoryEmpty(bool $expected, string $dir): void
    {
        self::assertSame($expected, FileHelper::isDirectoryEmpty($dir));
    }

    /**
     *
     */
    public function testIsDirEmptyExceptions(): void
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
     * @param string|null $expected
     * @param string $file
     * @param string|null $magicFile
     * @param bool $checkExtension
     * @throws InvalidConfigException
     */
    public function testGetMimeType(?string $expected, string $file, ?string $magicFile, bool $checkExtension): void
    {
        self::assertSame($expected, FileHelper::getMimeType($file, $magicFile, $checkExtension));
    }

    /**
     *
     */
    public function testGetMimeTypeExceptions(): void
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
     * @param string $expected
     * @param string $filename
     * @param array $options
     */
    public function testSanitizeFilename(string $expected, string $filename, array $options): void
    {
        self::assertSame($expected, FileHelper::sanitizeFilename($filename, $options));
    }

    /**
     * @dataProvider isSvgDataProvider
     * @param bool $expected
     * @param string $file
     * @param string|null $magicFile
     * @param bool $checkExtension
     */
    public function testIsSvg(bool $expected, string $file, ?string $magicFile, bool $checkExtension): void
    {
        self::assertSame($expected, FileHelper::isSvg($file, $magicFile, $checkExtension));
    }

    /**
     * @dataProvider isGifDataProvider
     * @param bool $expected
     * @param string $input
     * @param string|null $magicFile
     * @param bool $checkExtension
     */
    public function testIsGif(bool $expected, string $input, ?string $magicFile, bool $checkExtension): void
    {
        self::assertSame($expected, FileHelper::isGif($input, $magicFile, $checkExtension));
    }

    /**
     * @dataProvider writeToFileDataProvider
     * @param string|false $content
     * @param string $file
     * @param string $contents
     * @param array $options
     * @param bool $removeDir
     * @param string $removeableDir
     * @throws ErrorException
     */
    public function testWriteToFile(string|false $content, string $file, string $contents, array $options, bool $removeDir = false, string $removeableDir = ''): void
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
    public function testWriteToFileAppend(): void
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
    public function testWriteToFileExceptions(): void
    {
        $this->tester->expectThrowable(InvalidArgumentException::class, function() {
            FileHelper::writeToFile('notafile/folder', 'somecontent', ['createDirs' => false]);
        });
    }

    /**
     * @dataProvider findClosestFileDataProvider
     */
    public function testFindClosestFile(string|null|false $expected, string $dir, array $options = [])
    {
        if ($expected === false) {
            $this->expectException(InvalidArgumentException::class);
            FileHelper::findClosestFile($dir, $options);
        } else {
            self::assertSame($expected, FileHelper::findClosestFile($dir, $options));
        }
    }

    /**
     * @dataProvider uniqueNameDataProvider
     *
     * @param string $expectedPattern
     * @param string $baseName
     */
    public function testUniqueName(string $expectedPattern, string $baseName): void
    {
        $expectedPattern = str_replace('{id}', '[\w\.]{23}', $expectedPattern);
        self::assertRegExp("/^$expectedPattern$/", FileHelper::uniqueName($baseName));
    }

    /**
     * @return array
     */
    public static function normalizePathDataProvider(): array
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
            ['foo/bar', 'file://foo/bar', '/'],
            ['foo/bar', 'file://FILE://foo/bar', '/'],
        ];
    }

    /**
     * @return array
     */
    public static function absolutePathDataProvider(): array
    {
        return [
            ['/foo/bar', 'bar', '/foo', '/'],
            ['/foo/bar', '/foo/bar', null, '/'],
            ['\\foo\\bar', 'bar', '/foo', '\\'],
            [FileHelper::normalizePath(getcwd(), '/') . '/foo/bar', 'foo/bar', null, '/'],
            [FileHelper::normalizePath(getcwd(), '/') . '/baz/foo/bar', 'foo/bar', 'baz', '/'],
            ['C:/Documents/Newsletters/Summer2018.pdf', 'C:\Documents\Newsletters\Summer2018.pdf', null, '/'],
            ['C:\Documents\Newsletters\Summer2018.pdf', 'C:\Documents\Newsletters\Summer2018.pdf', null, '\\'],
            ['C:\Documents\Newsletters\c:\Documents\Newsletters\Summer2018.pdf', 'c:\Documents\Newsletters\Summer2018.pdf', 'C:\Documents\Newsletters', '\\'],
        ];
    }

    /**
     * @return array
     */
    public static function relativePathDataProvider(): array
    {
        return [
            ['bar/baz', '/foo/bar/baz', '/foo', '/'],
            ['bar\\baz', '/foo/bar/baz', '/foo', '\\'],
            ['/foo/bar/baz', '/foo/bar/baz', '/test', '/'],
        ];
    }

    /**
     * @return array
     */
    public static function isWithinDataProvider(): array
    {
        return [
            [true, '/foo/bar', '/foo'],
            [true, 'foo/bar', 'foo'],
            [true, 'foo/bar', getcwd() . '/foo'],
            [false, '/foo/bar', '\\foo\\bar'],
            [false, '/baz', '/foo'],
        ];
    }

    /**
     * @return array
     */
    public static function mimeTypeDataProvider(): array
    {
        return [
            ['application/pdf', dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            ['text/html', dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, true],
            ['image/gif', dirname(__DIR__, 3) . '/_data/assets/files/example-gif.gif', null, true],
            ['application/pdf', dirname(__DIR__, 3) . '/_data/assets/files/pdf-sample.pdf', null, true],
            ['image/svg+xml', dirname(__DIR__, 3) . '/_data/assets/files/gng.svg', null, true],
            ['application/xml', dirname(__DIR__, 3) . '/_data/assets/files/random.xml', null, true],
            ['text/plain', dirname(__DIR__, 3) . '/_data/assets/files/test.html', null, false],
            ['directory', __DIR__, null, true],
        ];
    }

    /**
     * @return array
     */
    public static function isSvgDataProvider(): array
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
    public static function isGifDataProvider(): array
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
    public static function isDirectoryEmptyDataProvider(): array
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
    public static function getExtensionByMimeTypeDataProvider(): array
    {
        return [
            ['jpg', 'image/jpeg'],
            ['svg', 'image/svg+xml'],
        ];
    }

    /**
     * @return array
     */
    public static function sanitizeFilenameDataProvider(): array
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
    public static function writeToFileDataProvider(): array
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
    public static function findClosestFileDataProvider(): array
    {
        return [
            [
                FileHelper::normalizePath(__DIR__ . '/sandbox/singlefile/foo.txt', '/'),
                __DIR__ . '/sandbox/singlefile',
            ],
            [
                FileHelper::normalizePath(__DIR__ . '/sandbox/singlefile/foo.txt', '/'),
                __DIR__ . '/sandbox/singlefile/nested',
                [
                    'except' => ['ignore*'],
                ],
            ],
            [
                null,
                '/',
                [
                    'only' => ['nonexistent.txt'],
                ],
            ],
            [
                false,
                __DIR__ . '/sandbox/singlefile/nonexistent',
            ],
        ];
    }

    /**
     * @return array
     */
    public static function uniqueNameDataProvider(): array
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
    protected function _before(): void
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
