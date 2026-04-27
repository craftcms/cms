<?php

declare(strict_types=1);

use CraftCms\Cms\Support\File;

beforeEach(function () {
    $this->sandboxPath = storage_path('framework/testing/file-test/'.uniqid('', true));
    File::ensureDirectoryExists($this->sandboxPath);
});

afterEach(function () {
    File::deleteDirectory($this->sandboxPath);
});

describe('normalizePath', function () {
    test('normalizes directory separators', function (string $expected, string $path) {
        expect(File::normalizePath($path, '/'))->toBe($expected);
    })->with([
        'forward slashes' => ['/foo/bar', '/foo/bar'],
        'backslashes to forward' => ['/foo/bar', '\\foo\\bar'],
        'mixed separators' => ['/foo/bar/baz', '/foo\\bar/baz'],
        'trailing slash removed' => ['/foo/bar', '/foo/bar/'],
        'trailing backslash removed' => ['/foo/bar', '/foo/bar\\'],
    ]);

    test('resolves dot segments', function (string $expected, string $path) {
        expect(File::normalizePath($path, '/'))->toBe($expected);
    })->with([
        'single dot removed' => ['/foo/bar', '/foo/./bar'],
        'double dot resolves parent' => ['/foo', '/foo/bar/..'],
        'multiple double dots' => ['.', '/foo/bar/../..'],
        'mixed dots' => ['/foo/baz', '/foo/bar/./../baz'],
        'dot at start' => ['foo/bar', './foo/bar'],
        'double dot beyond root' => ['..', 'foo/../..'],
    ]);

    test('strips file protocol wrapper', function (string $expected, string $path) {
        expect(File::normalizePath($path, '/'))->toBe($expected);
    })->with([
        'single file://' => ['/foo/bar', 'file:///foo/bar'],
        'double file://' => ['/foo/bar', 'file://file:///foo/bar'],
        'mixed case FILE://' => ['foo/bar', 'file://FILE://foo/bar'],
    ]);

    test('preserves UNC paths', function () {
        expect(File::normalizePath('//server/share/file', '/'))
            ->toBe('//server/share/file');

        expect(File::normalizePath('\\\\server\\share\\file', '/'))
            ->toBe('//server/share/file');
    });

    test('collapses double separators', function () {
        expect(File::normalizePath('/foo//bar///baz', '/'))
            ->toBe('/foo/bar/baz');
    });

    test('returns dot for empty result', function () {
        expect(File::normalizePath('.', '/'))
            ->toBe('.');
    });

    test('passes through plain strings without separators', function () {
        expect(File::normalizePath('Im a string', DIRECTORY_SEPARATOR))
            ->toBe('Im a string');
    });

    test('uses custom directory separators', function (string $expected, string $path, string $ds) {
        expect(File::normalizePath($path, $ds))->toBe($expected);
    })->with([
        'pipe separator' => ['|foo|bar', '/foo/bar', '|'],
        'pipe with backslash input' => ['c:|vagrant|box', 'c:\\vagrant\\box', '|'],
        'plus separator with UNC-like path' => [' +HostName[@SSL][@Port]+SharedFolder+Resource', ' \\HostName[@SSL][@Port]\SharedFolder\Resource', '+'],
        'pipe with extended-length prefix' => ['|?|C:|my_dir', '\\?\C:\my_dir', '|'],
        'equals with UNC prefix' => ['==stuff', '\\\\stuff', '='],
    ]);

    test('handles Windows drive letter paths', function () {
        expect(File::normalizePath('c:/vagrant/box', '\\'))
            ->toBe('c:\\vagrant\\box');
    });

    test('preserves stream wrappers', function () {
        $path = 'phar:///path/to/file.phar/internal/path';
        expect(File::normalizePath($path, '/'))->toBe($path);
    });
});

describe('relativePath', function () {
    test('returns relative path when target is within source', function () {
        expect(File::relativePath('/home/user/project/src/file.php', '/home/user/project', '/'))
            ->toBe('src/file.php');
    });

    test('returns dot when target equals source', function () {
        expect(File::relativePath('/home/user', '/home/user', '/'))
            ->toBe('.');
    });

    test('returns absolute path when target is not within source', function () {
        expect(File::relativePath('/other/path', '/home/user', '/'))
            ->toBe('/other/path');
    });

    test('handles path prefix edge case', function () {
        expect(File::relativePath('/home/username', '/home/user', '/'))
            ->toBe('/home/username');
    });

    test('uses backslash separator in output', function () {
        expect(File::relativePath('/foo/bar/baz', '/foo', '\\'))
            ->toBe('bar\\baz');
    });

    test('returns absolute path when from does not match', function () {
        expect(File::relativePath('/foo/bar/baz', '/test', '/'))
            ->toBe('/foo/bar/baz');
    });
});

describe('absolutePath', function () {
    test('returns already absolute paths unchanged', function () {
        expect(File::absolutePath('/foo/bar', '/home/user', '/'))->toBe('/foo/bar');
    });

    test('resolves relative paths from source', function () {
        expect(File::absolutePath('src/file.php', '/home/user', '/'))
            ->toBe('/home/user/src/file.php');
    });

    test('resolves dot segments in relative paths', function () {
        expect(File::absolutePath('../other/file.php', '/home/user/project', '/'))
            ->toBe('/home/user/other/file.php');
    });

    test('detects Windows drive letters as absolute', function () {
        expect(File::absolutePath('C:\\foo\\bar', null, '\\'))
            ->toBe('C:\\foo\\bar');
    });

    test('produces backslash output with backslash separator', function () {
        expect(File::absolutePath('bar', '/foo', '\\'))
            ->toBe('\\foo\\bar');
    });

    test('uses cwd when from is null', function () {
        $cwd = File::normalizePath(getcwd(), '/');
        expect(File::absolutePath('foo/bar', null, '/'))
            ->toBe($cwd.'/foo/bar');
    });

    test('resolves relative from path against cwd', function () {
        $cwd = File::normalizePath(getcwd(), '/');
        expect(File::absolutePath('foo/bar', 'baz', '/'))
            ->toBe($cwd.'/baz/foo/bar');
    });

    test('handles uppercase Windows drive letter as absolute', function () {
        expect(File::absolutePath('C:\Documents\Newsletters\Summer2018.pdf', null, '/'))
            ->toBe('C:/Documents/Newsletters/Summer2018.pdf');
    });

    test('does not treat lowercase Windows drive letter as absolute with backslash separator', function () {
        expect(File::absolutePath('c:\Documents\Newsletters\Summer2018.pdf', 'C:\Documents\Newsletters', '\\'))
            ->toBe('C:\Documents\Newsletters\c:\Documents\Newsletters\Summer2018.pdf');
    });
});

describe('sanitizeFilename', function () {
    test('strips disallowed characters', function (string $expected, string $filename) {
        expect(File::sanitizeFilename($filename, ['stripEmoji' => true]))->toBe($expected);
    })->with([
        'slashes' => ['foobar', 'foo/bar'],
        'backslashes' => ['foobar', 'foo\\bar'],
        'question marks' => ['foobar', 'foo?bar'],
        'colons' => ['foobar', 'foo:bar'],
        'asterisks' => ['foobar', 'foo*bar'],
        'pipes' => ['foobar', 'foo|bar'],
        'angle brackets' => ['foo', 'foo<bar>'],
        'quotes' => ['foobar', 'foo"bar'],
        'hash' => ['foobar', 'foo#bar'],
        'percent' => ['foobar', 'foo%bar'],
        'ampersand' => ['foobar', 'foo&bar'],
        'multiple disallowed' => ['foobar', 'foo<>"/\\|?*bar'],
        'preserves dots' => ['foo.bar', 'foo.bar'],
        'preserves dashes' => ['foo-bar', 'foo-bar'],
        'preserves underscores' => ['foo_bar', 'foo_bar'],
    ]);

    test('trims leading and trailing special chars', function () {
        expect(File::sanitizeFilename('.foo.', ['stripEmoji' => true]))->toBe('foo');
        expect(File::sanitizeFilename('-foo-', ['stripEmoji' => true]))->toBe('foo');
        expect(File::sanitizeFilename('_foo_', ['stripEmoji' => true]))->toBe('foo');
        expect(File::sanitizeFilename('.-_foo_-.', ['stripEmoji' => true]))->toBe('foo');
    });

    test('replaces whitespace with separator', function () {
        expect(File::sanitizeFilename('foo bar baz', ['stripEmoji' => true]))
            ->toBe('foo-bar-baz');
    });

    test('uses custom separator', function () {
        expect(File::sanitizeFilename('foo bar', ['separator' => '_', 'stripEmoji' => true]))
            ->toBe('foo_bar');
    });

    test('preserves whitespace when separator is null', function () {
        expect(File::sanitizeFilename('foo bar', ['separator' => null, 'stripEmoji' => true]))
            ->toBe('foo bar');
    });

    test('strips emoji when requested', function () {
        expect(File::sanitizeFilename('foo🔥bar', ['stripEmoji' => true]))
            ->toBe('foobar');
    });

    test('preserves emoji when stripEmoji is false', function () {
        expect(File::sanitizeFilename('foo🔥bar', ['stripEmoji' => false]))
            ->toBe('foo🔥bar');
    });

    test('collapses multiple separators', function () {
        expect(File::sanitizeFilename('foo   bar', ['stripEmoji' => true]))
            ->toBe('foo-bar');
    });

    test('strips HTML tags', function () {
        expect(File::sanitizeFilename('foo<b>bar</b>baz', ['stripEmoji' => true]))
            ->toBe('foobarbaz');
    });

    test('preserves at sign and strips other special chars with extension', function () {
        expect(File::sanitizeFilename('im-a-file!@#$%^&*(.svg'))
            ->toBe('im-a-file@.svg');
    });

    test('converts non-ascii to ascii when asciiOnly is true', function () {
        expect(File::sanitizeFilename('i£©m-a-file⚽🐧🎺.svg', ['asciiOnly' => true]))
            ->toBe('iPS(c)m-a-file.svg');
    });

    test('uses multi-character separator', function () {
        expect(File::sanitizeFilename('not a file', ['separator' => '||']))
            ->toBe('not||a||file');
    });

    test('uses emoji separator with asciiOnly', function () {
        expect(File::sanitizeFilename('not a file', ['separator' => '🐧', 'asciiOnly' => true]))
            ->toBe('not🐧a🐧file');
    });
});

describe('canTrustMimeType', function () {
    test('returns false for untrusted types', function (string $mimeType) {
        expect(File::canTrustMimeType($mimeType))->toBeFalse();
    })->with([
        'application/octet-stream',
        'application/xml',
        'text/html',
        'text/plain',
        'text/xml',
    ]);

    test('returns true for trusted types', function (string $mimeType) {
        expect(File::canTrustMimeType($mimeType))->toBeTrue();
    })->with([
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/svg+xml',
        'application/pdf',
        'application/json',
        'video/mp4',
        'audio/mpeg',
    ]);
});

describe('getMimeTypeByExtension', function () {
    test('returns correct types', function (string $expectedMime, string $file) {
        expect(File::getMimeTypeByExtension($file))->toBe($expectedMime);
    })->with([
        'jpg' => ['image/jpeg', 'photo.jpg'],
        'png' => ['image/png', 'image.png'],
        'gif' => ['image/gif', 'animation.gif'],
        'svg' => ['image/svg+xml', 'icon.svg'],
        'pdf' => ['application/pdf', 'document.pdf'],
        'mp4' => ['application/mp4', 'video.mp4'],
        'mp3' => ['audio/mpeg', 'song.mp3'],
        'json' => ['application/json', 'data.json'],
        'css' => ['text/css', 'styles.css'],
        'js' => ['text/javascript', 'app.js'],
    ]);

    test('returns null for no extension', function () {
        expect(File::getMimeTypeByExtension('noextension'))->toBeNull();
    });
});

describe('getExtensionByMimeType', function () {
    test('returns known extensions', function (string $expectedExt, string $mimeType) {
        expect(File::getExtensionByMimeType($mimeType))->toBe($expectedExt);
    })->with([
        'doc' => ['doc', 'application/msword'],
        'yml' => ['yml', 'application/x-yaml'],
        'xml' => ['xml', 'application/xml'],
        'm4a' => ['m4a', 'audio/mp4'],
        'mp3' => ['mp3', 'audio/mpeg'],
        'ogg' => ['ogg', 'audio/ogg'],
        'heic' => ['heic', 'image/heic'],
        'jpg' => ['jpg', 'image/jpeg'],
        'svg' => ['svg', 'image/svg+xml'],
        'tif' => ['tif', 'image/tiff'],
        'ics' => ['ics', 'text/calendar'],
        'html' => ['html', 'text/html'],
        'md' => ['md', 'text/markdown'],
        'txt' => ['txt', 'text/plain'],
        'mp4' => ['mp4', 'video/mp4'],
        'mpg' => ['mpg', 'video/mpeg'],
        'mov' => ['mov', 'video/quicktime'],
        'png' => ['png', 'image/png'],
        'gif' => ['gif', 'image/gif'],
        'pdf' => ['pdf', 'application/pdf'],
    ]);

    test('throws for unknown mime type', function () {
        File::getExtensionByMimeType('application/x-totally-unknown-made-up');
    })->throws(InvalidArgumentException::class);

    test('is case insensitive', function () {
        expect(File::getExtensionByMimeType('IMAGE/JPEG'))->toBe('jpg');
        expect(File::getExtensionByMimeType('Text/HTML'))->toBe('html');
    });
});

describe('uniqueName', function () {
    test('generates a name with uniqid prefix and correct extension', function () {
        $result = File::uniqueName('photo.jpg');

        expect($result)->toEndWith('.jpg');
        expect($result)->toContain('photo');
        expect(strlen($result))->toBeGreaterThan(strlen('photo.jpg'));
    });

    test('handles files without extension', function () {
        $result = File::uniqueName('README');

        expect($result)->toContain('README');
    });

    test('handles dotfile-like input', function () {
        $result = File::uniqueName('.gitignore');

        expect($result)->toEndWith('.gitignore');
    });

    test('truncates long filenames', function () {
        $longName = str_repeat('a', 300).'.txt';
        $result = File::uniqueName($longName);

        expect(strlen($result))->toBeLessThanOrEqual(255);
        expect($result)->toEndWith('.txt');
    });

    test('generates unique values on each call', function () {
        $result1 = File::uniqueName('test.txt');
        $result2 = File::uniqueName('test.txt');

        expect($result1)->not->toBe($result2);
    });

    test('handles empty string input', function () {
        $result = File::uniqueName('');

        expect($result)->not->toBeEmpty();
        expect($result)->toMatch('/[\w.]{23}/');
    });

    test('handles extension-only input', function () {
        $result = File::uniqueName('.ext');

        expect($result)->toEndWith('.ext');
    });

    test('matches expected pattern with uniqid format', function (string $expectedPattern, string $baseName) {
        $regex = str_replace('{id}', '[\w\.]{23}', $expectedPattern);
        expect(File::uniqueName($baseName))->toMatch("/^$regex$/");
    })->with([
        'empty' => ['{id}', ''],
        'name only' => ['foo{id}', 'foo'],
        'extension only' => ['{id}\.ext', '.ext'],
        'name with extension' => ['foo{id}\.ext', 'foo.ext'],
    ]);
});

describe('makeDirectory', function () {
    test('creates a new directory', function () {
        $dir = $this->sandboxPath.'/new-dir';

        expect(is_dir($dir))->toBeFalse();

        $result = File::makeDirectory($dir);

        expect($result)->toBeTrue();
        expect(is_dir($dir))->toBeTrue();
    });

    test('returns true for existing directory', function () {
        expect(File::makeDirectory($this->sandboxPath))->toBeTrue();
    });

    test('creates nested directories recursively', function () {
        $dir = $this->sandboxPath.'/a/b/c/d';

        $result = File::makeDirectory($dir);

        expect($result)->toBeTrue();
        expect(is_dir($dir))->toBeTrue();
    });
});

describe('cleanDirectory', function () {
    test('removes all contents', function () {
        $dir = $this->sandboxPath.'/clean-test';
        File::makeDirectory($dir);

        file_put_contents($dir.'/file1.txt', 'content');
        file_put_contents($dir.'/file2.txt', 'content');
        File::makeDirectory($dir.'/subdir');
        file_put_contents($dir.'/subdir/file3.txt', 'content');

        $result = File::cleanDirectory($dir);

        expect($result)->toBeTrue();
        expect(is_dir($dir))->toBeTrue();
        expect(count(glob($dir.'/*')))->toBe(0);
    });

    test('returns false for non-existent directory', function () {
        expect(File::cleanDirectory($this->sandboxPath.'/does-not-exist'))->toBeFalse();
    });

    test('clears an already empty directory without error', function () {
        $dir = $this->sandboxPath.'/already-empty';
        File::makeDirectory($dir);

        $result = File::cleanDirectory($dir);

        expect($result)->toBeTrue();
        expect(is_dir($dir))->toBeTrue();
        expect(count(glob($dir.'/*')))->toBe(0);
    });

    test('respects except patterns for files', function () {
        $dir = $this->sandboxPath.'/except-test';
        File::makeDirectory($dir);

        file_put_contents($dir.'/keep.txt', 'keep');
        file_put_contents($dir.'/remove.log', 'remove');
        file_put_contents($dir.'/also-keep.txt', 'keep');

        File::cleanDirectory($dir, ['*.txt']);

        expect(is_file($dir.'/keep.txt'))->toBeTrue();
        expect(is_file($dir.'/also-keep.txt'))->toBeTrue();
        expect(is_file($dir.'/remove.log'))->toBeFalse();
    });

    test('respects except patterns for directories', function () {
        $dir = $this->sandboxPath.'/except-dir-test';
        File::makeDirectory($dir);

        File::makeDirectory($dir.'/keep-dir');
        file_put_contents($dir.'/keep-dir/file.txt', 'content');
        File::makeDirectory($dir.'/remove-dir');
        file_put_contents($dir.'/remove-dir/file.txt', 'content');

        File::cleanDirectory($dir, ['keep-dir/']);

        expect(is_dir($dir.'/keep-dir'))->toBeTrue();
        expect(is_file($dir.'/keep-dir/file.txt'))->toBeTrue();
        expect(is_dir($dir.'/remove-dir'))->toBeFalse();
    });
});

describe('writeToFile', function () {
    test('creates file with contents', function () {
        $file = $this->sandboxPath.'/test-write.txt';

        File::writeToFile($file, 'hello world');

        expect(is_file($file))->toBeTrue();
        expect(file_get_contents($file))->toBe('hello world');
    });

    test('overwrites existing contents', function () {
        $file = $this->sandboxPath.'/overwrite.txt';

        File::writeToFile($file, 'first');
        File::writeToFile($file, 'second');

        expect(file_get_contents($file))->toBe('second');
    });

    test('appends when requested', function () {
        $file = $this->sandboxPath.'/append.txt';

        File::writeToFile($file, 'hello', append: false);
        File::writeToFile($file, ' world', append: true);

        expect(file_get_contents($file))->toBe('hello world');
    });

    test('creates parent directories automatically', function () {
        $file = $this->sandboxPath.'/nested/deep/dir/file.txt';

        File::writeToFile($file, 'content');

        expect(is_file($file))->toBeTrue();
        expect(file_get_contents($file))->toBe('content');
    });
});

describe('writeGitignoreFile', function () {
    test('creates gitignore in directory', function () {
        $dir = $this->sandboxPath.'/gitignore-test';
        File::makeDirectory($dir);

        File::writeGitignoreFile($dir);

        $gitignorePath = $dir.'/.gitignore';
        expect(is_file($gitignorePath))->toBeTrue();
        expect(file_get_contents($gitignorePath))->toBe("*\n!.gitignore\n");
    });

    test('does not overwrite existing gitignore', function () {
        $dir = $this->sandboxPath.'/gitignore-existing';
        File::makeDirectory($dir);

        $gitignorePath = $dir.'/.gitignore';
        file_put_contents($gitignorePath, 'custom content');

        File::writeGitignoreFile($dir);

        expect(file_get_contents($gitignorePath))->toBe('custom content');
    });
});

describe('cycle', function () {
    test('rotates files forward', function () {
        $base = $this->sandboxPath.'/logfile.log';

        file_put_contents($base, 'current');

        File::cycle($base, 3);

        expect(is_file($base))->toBeFalse();
        expect(is_file($base.'.1'))->toBeTrue();
        expect(file_get_contents($base.'.1'))->toBe('current');
    });

    test('handles multiple existing files', function () {
        $base = $this->sandboxPath.'/logfile.log';

        file_put_contents($base, 'current');
        file_put_contents($base.'.1', 'previous');
        file_put_contents($base.'.2', 'oldest');

        File::cycle($base, 5);

        expect(is_file($base))->toBeFalse();
        expect(file_get_contents($base.'.1'))->toBe('current');
        expect(file_get_contents($base.'.2'))->toBe('previous');
        expect(file_get_contents($base.'.3'))->toBe('oldest');
    });

    test('deletes file at max position', function () {
        $base = $this->sandboxPath.'/logfile.log';

        file_put_contents($base, 'current');
        file_put_contents($base.'.1', 'previous');
        file_put_contents($base.'.2', 'at-max');

        File::cycle($base, 3);

        expect(is_file($base))->toBeFalse();
        expect(file_get_contents($base.'.1'))->toBe('current');
        expect(file_get_contents($base.'.2'))->toBe('previous');
        expect(is_file($base.'.3'))->toBeFalse();
    });

    test('handles no existing files gracefully', function () {
        $base = $this->sandboxPath.'/nonexistent.log';

        File::cycle($base);

        expect(is_file($base))->toBeFalse();
        expect(is_file($base.'.1'))->toBeFalse();
    });
});

describe('getMimeType', function () {
    test('detects mime type from file content', function () {
        $file = $this->sandboxPath.'/test.txt';
        file_put_contents($file, 'Hello, world!');

        expect(File::getMimeType($file))->toBe('text/plain');
    });

    test('returns directory for directories', function () {
        expect(File::getMimeType($this->sandboxPath))->toBe('directory');
    });

    test('falls back to extension when checkExtension is true', function () {
        $file = $this->sandboxPath.'/test.svg';
        file_put_contents($file, 'not really an svg');

        expect(File::getMimeType($file, checkExtension: true))->toBe('image/svg+xml');
    });

    test('detects image types', function () {
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        $file = $this->sandboxPath.'/pixel.png';
        file_put_contents($file, $png);

        expect(File::getMimeType($file))->toBe('image/png');
    });

    test('detects mime types from real fixture files', function (string $expectedMime, string $filename) {
        $fixtures = dirname(__DIR__, 2).'/../../yii2-adapter/tests/_data/assets/files';
        $file = $fixtures.'/'.$filename;

        if (! file_exists($file)) {
            $this->markTestSkipped("Fixture file $filename not found");
        }

        expect(File::getMimeType($file))->toBe($expectedMime);
    })->with([
        'pdf' => ['application/pdf', 'pdf-sample.pdf'],
        'html with extension check' => ['text/html', 'test.html'],
        'gif' => ['image/gif', 'example-gif.gif'],
        'svg' => ['image/svg+xml', 'gng.svg'],
        'xml' => ['application/xml', 'random.xml'],
    ]);

    test('returns text/plain for html when checkExtension is false', function () {
        $fixtures = dirname(__DIR__, 2).'/../../yii2-adapter/tests/_data/assets/files';
        $file = $fixtures.'/test.html';

        if (! file_exists($file)) {
            $this->markTestSkipped('Fixture file test.html not found');
        }

        expect(File::getMimeType($file, checkExtension: false))->toBe('text/plain');
    });

    test('detects empty file', function () {
        $file = $this->sandboxPath.'/empty.txt';
        file_put_contents($file, '');

        $mime = File::getMimeType($file);

        expect($mime)->toBeString();
    });
});

describe('isSvg', function () {
    test('returns true for svg files', function () {
        $file = $this->sandboxPath.'/icon.svg';
        file_put_contents($file, '<svg xmlns="http://www.w3.org/2000/svg"></svg>');

        expect(File::isSvg($file))->toBeTrue();
    });

    test('returns false for non-svg files', function () {
        $file = $this->sandboxPath.'/photo.txt';
        file_put_contents($file, 'not an svg');

        expect(File::isSvg($file))->toBeFalse();
    });
});

describe('isGif', function () {
    test('returns true for gif files', function () {
        $gif = "GIF89a\x01\x00\x01\x00\x80\x00\x00\xff\xff\xff\x00\x00\x00!\xf9\x04\x00\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;";
        $file = $this->sandboxPath.'/animation.gif';
        file_put_contents($file, $gif);

        expect(File::isGif($file))->toBeTrue();
    });

    test('returns false for non-gif files', function () {
        $file = $this->sandboxPath.'/photo.jpg';
        file_put_contents($file, 'not a gif');

        expect(File::isGif($file))->toBeFalse();
    });
});

describe('zip', function () {
    test('creates a zip from a file', function () {
        $file = $this->sandboxPath.'/to-zip.txt';
        file_put_contents($file, 'zip me');

        $zipPath = File::zip($file);

        expect($zipPath)->toBe($file.'.zip');
        expect(is_file($zipPath))->toBeTrue();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        expect($zip->numFiles)->toBe(1);
        expect($zip->getNameIndex(0))->toBe('to-zip.txt');
        $zip->close();
    });

    test('creates a zip from a directory', function () {
        $dir = $this->sandboxPath.'/zip-dir';
        File::makeDirectory($dir);
        file_put_contents($dir.'/file1.txt', 'content1');
        file_put_contents($dir.'/file2.txt', 'content2');

        $zipPath = File::zip($dir);

        expect(is_file($zipPath))->toBeTrue();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        expect($zip->numFiles)->toBe(2);
        $zip->close();
    });

    test('uses custom target path', function () {
        $file = $this->sandboxPath.'/source.txt';
        file_put_contents($file, 'content');

        $target = $this->sandboxPath.'/custom.zip';
        $zipPath = File::zip($file, $target);

        expect($zipPath)->toBe($target);
        expect(is_file($target))->toBeTrue();
    });

    test('throws for non-existent path', function () {
        File::zip($this->sandboxPath.'/does-not-exist');
    })->throws(InvalidArgumentException::class);
});

describe('addFilesToZip', function () {
    test('adds files with prefix', function () {
        $dir = $this->sandboxPath.'/add-to-zip';
        File::makeDirectory($dir);
        file_put_contents($dir.'/file.txt', 'content');

        $zipPath = $this->sandboxPath.'/prefixed.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        File::addFilesToZip($zip, $dir, 'my-prefix');

        $zip->close();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        expect($zip->getNameIndex(0))->toContain('my-prefix/');
        $zip->close();
    });

    test('filters with only patterns', function () {
        $dir = $this->sandboxPath.'/only-filter';
        File::makeDirectory($dir);
        file_put_contents($dir.'/include.php', '<?php');
        file_put_contents($dir.'/exclude.txt', 'text');

        $zipPath = $this->sandboxPath.'/only.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        File::addFilesToZip($zip, $dir, only: ['*.php']);

        $zip->close();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        expect($zip->numFiles)->toBe(1);
        expect($zip->getNameIndex(0))->toContain('include.php');
        $zip->close();
    });

    test('filters with except patterns', function () {
        $dir = $this->sandboxPath.'/except-filter';
        File::makeDirectory($dir);
        file_put_contents($dir.'/keep.txt', 'keep');
        file_put_contents($dir.'/remove.log', 'remove');

        $zipPath = $this->sandboxPath.'/except.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        File::addFilesToZip($zip, $dir, except: ['*.log']);

        $zip->close();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        expect($zip->numFiles)->toBe(1);
        expect($zip->getNameIndex(0))->toContain('keep.txt');
        $zip->close();
    });

    test('respects recursive flag', function () {
        $dir = $this->sandboxPath.'/recursive-test';
        File::makeDirectory($dir.'/sub');
        file_put_contents($dir.'/root.txt', 'root');
        file_put_contents($dir.'/sub/nested.txt', 'nested');

        $zipPath = $this->sandboxPath.'/non-recursive.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        File::addFilesToZip($zip, $dir, recursive: false);

        $zip->close();

        $zip = new ZipArchive;
        $zip->open($zipPath);
        expect($zip->numFiles)->toBe(1);
        expect($zip->getNameIndex(0))->toContain('root.txt');
        $zip->close();
    });

    test('handles non-existent directory gracefully', function () {
        $zipPath = $this->sandboxPath.'/empty.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE);

        File::addFilesToZip($zip, $this->sandboxPath.'/does-not-exist');

        expect($zip->numFiles)->toBe(0);
        $zip->close();
    });
});

describe('invalidate', function () {
    test('clears stat cache for file', function () {
        $file = $this->sandboxPath.'/cached.txt';
        file_put_contents($file, 'original');

        $originalSize = filesize($file);

        file_put_contents($file, 'longer content here');

        File::invalidate($file);

        clearstatcache(true, $file);
        expect(filesize($file))->not->toBe($originalSize);
    });
});
