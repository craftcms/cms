<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\AssetFileKinds;
use CraftCms\Cms\Asset\AssetsHelper;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Asset\Events\SetAssetFilename;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Path;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Cms::setIsInstalled(false);
});

describe('prepareAssetName', function () {
    test('returns expected results for various inputs', function (string $expected, string $name, bool $isFilename, bool $preventPluginModifications) {
        expect(AssetsHelper::prepareAssetName($name, $isFilename, $preventPluginModifications))->toBe($expected);
    })->with([
        'simple name without extension' => ['name', 'name', true, false],
        'preserves uppercase' => ['NAME', 'NAME', true, false],
        'strips trailing dot' => ['name', 'name.', true, false],
        'sanitizes special characters' => ['te-@st.notaf ile', 'te !@#$%^&*()st.notaf ile', true, false],
        'sanitizes special characters with preventPluginModifications' => ['te-@st.notaf ile', 'te !@#$%^&*()st.notaf ile', true, true],
        'empty string with isFilename false returns empty' => ['', '', false, false],
        'empty string with isFilename true returns dash' => ['-', '', true, false],
        'truncates to 255 chars including extension' => [str_repeat('o', 251).'.jpg', str_repeat('o', 252).'.jpg', true, false],
        'cleans simple filename' => ['my-file.jpg', 'my file.jpg', true, false],
        'preserves extension when isFilename is true' => ['document.pdf', 'document.pdf', true, false],
        'does not add extension when isFilename is false' => ['folder-name', 'folder name', false, false],
        'handles files with no extension' => ['README', 'README', true, false],
        'handles filename with multiple dots' => ['my.file.name.jpg', 'my.file.name.jpg', true, false],
    ]);

    test('converts filenames to ascii when config enabled', function () {
        Cms::config()->convertFilenamesToAscii = true;

        expect(AssetsHelper::prepareAssetName('tes§t.text'))->toBe('tesSSt.text');
    });

    test('uses multi-character filenameWordSeparator from config', function () {
        Cms::config()->filenameWordSeparator = '||';

        expect(AssetsHelper::prepareAssetName('te st.notafile'))->toBe('te||st.notafile');
    });

    test('preserves spaces when filenameWordSeparator is false', function () {
        Cms::config()->filenameWordSeparator = false;

        expect(AssetsHelper::prepareAssetName('t est.notafile'))->toBe('t est.notafile');
    });

    test('fires SetAssetFilename event when isFilename is true', function () {
        Event::fake([SetAssetFilename::class]);

        AssetsHelper::prepareAssetName('test.jpg');

        Event::assertDispatched(fn (SetAssetFilename $event) => $event->extension === '.jpg');
    });

    test('does not fire SetAssetFilename event when isFilename is false', function () {
        Event::fake([SetAssetFilename::class]);

        AssetsHelper::prepareAssetName('test folder', false);

        Event::assertNotDispatched(SetAssetFilename::class);
    });

    test('does not fire SetAssetFilename event when preventPluginModifications is true', function () {
        Event::fake([SetAssetFilename::class]);

        AssetsHelper::prepareAssetName('test.jpg', true, true);

        Event::assertNotDispatched(SetAssetFilename::class);
    });

    test('allows event listeners to modify the filename', function () {
        Event::listen(SetAssetFilename::class, function (SetAssetFilename $event) {
            $event->filename = 'modified';
        });

        $result = AssetsHelper::prepareAssetName('original.jpg');

        expect($result)->toBe('modified.jpg');
    });

    test('allows event listeners to modify the extension', function () {
        Event::listen(SetAssetFilename::class, function (SetAssetFilename $event) {
            $event->extension = '.png';
        });

        $result = AssetsHelper::prepareAssetName('photo.jpg');

        expect($result)->toEndWith('.png');
    });
});

describe('filename2Title', function () {
    test('converts filename to title', function (string $filename, string $expected) {
        expect(AssetsHelper::filename2Title($filename))->toBe($expected);
    })->with([
        'simple word' => ['filename', 'Filename'],
        'hyphenated words' => ['my-file-name', 'My file name'],
        'underscored words' => ['my_file_name', 'My file name'],
        'camelCase' => ['myFileName', 'My File Name'],
        'dots underscores hyphens and spaces' => ['file.name_is-with chars', 'File name is with chars'],
        'special characters stripped' => ['file.name_is-with chars!@#$%^&*()', 'File name is with chars'],
    ]);

    test('truncates titles longer than 255 characters', function () {
        $longName = str_repeat('a', 300);

        $result = AssetsHelper::filename2Title($longName);

        expect(strlen($result))->toBeLessThanOrEqual(255);
    });
});

describe('getFileKindByExtension', function () {
    test('returns correct kind for known extensions', function (string $file, string $expectedKind) {
        expect(AssetsHelper::getFileKindByExtension($file))->toBe($expectedKind);
    })->with([
        'jpg image' => ['photo.jpg', FileKind::Image->value],
        'jpeg image' => ['photo.jpeg', FileKind::Image->value],
        'png image' => ['photo.png', FileKind::Image->value],
        'gif image' => ['photo.gif', FileKind::Image->value],
        'webp image' => ['photo.webp', FileKind::Image->value],
        'svg image' => ['photo.svg', FileKind::Image->value],
        'avif image' => ['photo.avif', FileKind::Image->value],
        'mp3 audio' => ['song.mp3', FileKind::Audio->value],
        'wav audio' => ['song.wav', FileKind::Audio->value],
        'flac audio' => ['song.flac', FileKind::Audio->value],
        'mp4 video' => ['movie.mp4', FileKind::Video->value],
        'mov video' => ['movie.mov', FileKind::Video->value],
        'webm video' => ['movie.webm', FileKind::Video->value],
        'pdf document' => ['doc.pdf', FileKind::Pdf->value],
        'json file' => ['data.json', FileKind::Json->value],
        'xml file' => ['data.xml', FileKind::Xml->value],
        'html file' => ['page.html', FileKind::Html->value],
        'htm file' => ['page.htm', FileKind::Html->value],
        'js file' => ['script.js', FileKind::Javascript->value],
        'php file' => ['script.php', FileKind::Php->value],
        'txt file' => ['readme.txt', FileKind::Text->value],
        'zip compressed' => ['archive.zip', FileKind::Compressed->value],
        'doc word' => ['document.doc', FileKind::Word->value],
        'docx word' => ['document.docx', FileKind::Word->value],
        'xls excel' => ['sheet.xls', FileKind::Excel->value],
        'xlsx excel' => ['sheet.xlsx', FileKind::Excel->value],
        'ppt powerpoint' => ['slides.ppt', FileKind::Powerpoint->value],
        'pptx powerpoint' => ['slides.pptx', FileKind::Powerpoint->value],
        'psd photoshop' => ['design.psd', FileKind::Photoshop->value],
        'ai illustrator' => ['design.ai', FileKind::Illustrator->value],
        'srt subtitles' => ['subtitles.srt', FileKind::CaptionsSubtitles->value],
        'vtt subtitles' => ['subtitles.vtt', FileKind::CaptionsSubtitles->value],
        'accdb access' => ['file.accdb', FileKind::Access->value],
    ]);

    test('returns unknown for unrecognized extensions', function () {
        expect(AssetsHelper::getFileKindByExtension('file.xyz123'))->toBe(FileKind::Unknown->value);
    });

    test('returns unknown for files without extension', function () {
        expect(AssetsHelper::getFileKindByExtension('README'))->toBe(FileKind::Unknown->value);
    });

    test('returns unknown for bare extension name without dot', function () {
        expect(AssetsHelper::getFileKindByExtension('html'))->toBe(FileKind::Unknown->value);
    });

    test('is case insensitive', function () {
        expect(AssetsHelper::getFileKindByExtension('photo.JPG'))->toBe(FileKind::Image->value);
        expect(AssetsHelper::getFileKindByExtension('photo.Png'))->toBe(FileKind::Image->value);
    });

    test('handles full paths', function () {
        expect(AssetsHelper::getFileKindByExtension('/path/to/photo.jpg'))->toBe(FileKind::Image->value);
    });
});

describe('getFileKindLabel', function () {
    test('returns correct label for known kinds', function (string $kind, string $expectedLabel) {
        expect(AssetsHelper::getFileKindLabel($kind))->toBe($expectedLabel);
    })->with([
        'access' => ['access', 'Access'],
        'audio' => ['audio', 'Audio'],
        'text' => ['text', 'Text'],
        'php' => ['php', 'PHP'],
        'image' => ['image', 'Image'],
        'video' => ['video', 'Video'],
        'pdf' => ['pdf', 'PDF'],
        'compressed' => ['compressed', 'Compressed'],
    ]);

    test('returns unknown for unrecognized kind', function () {
        expect(AssetsHelper::getFileKindLabel('Raaa'))->toBe(FileKind::Unknown->value);
        expect(AssetsHelper::getFileKindLabel('nonexistent_kind'))->toBe(FileKind::Unknown->value);
    });
});

describe('parseFileLocation', function () {
    test('parses valid file locations', function (string $location, int $expectedFolderId, string $expectedFilename) {
        [$folderId, $filename] = AssetsHelper::parseFileLocation($location);

        expect($folderId)->toBe($expectedFolderId);
        expect($filename)->toBe($expectedFilename);
    })->with([
        'simple' => ['{folder:123}photo.jpg', 123, 'photo.jpg'],
        'folder id 1' => ['{folder:1}test.txt', 1, 'test.txt'],
        'large folder id' => ['{folder:99999}document.pdf', 99999, 'document.pdf'],
        'filename with spaces' => ['{folder:5}my file.jpg', 5, 'my file.jpg'],
        'filename with path' => ['{folder:10}subfolder/photo.jpg', 10, 'subfolder/photo.jpg'],
        'dot filename' => ['{folder:2}.', 2, '.'],
        'special characters in filename' => ['{folder:2}.!@#$%^&*()', 2, '.!@#$%^&*()'],
    ]);

    test('throws on invalid format', function (string $location) {
        AssetsHelper::parseFileLocation($location);
    })->with([
        'no folder prefix' => ['photo.jpg'],
        'empty string' => [''],
        'wrong format' => ['{volume:123}photo.jpg'],
        'special characters only' => ['!@#$%^&*()_'],
        'non-numeric folder id' => ['{folder:string}.'],
    ])->throws(InvalidArgumentException::class);

    test('throws when filename is missing', function () {
        expect(fn () => AssetsHelper::parseFileLocation('{folder:123}'))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('parseSrcsetSize', function () {
    test('parses valid srcset sizes', function (mixed $input, float $expectedValue, string $expectedUnit) {
        [$value, $unit] = AssetsHelper::parseSrcsetSize($input);

        expect($value)->toBe($expectedValue);
        expect($unit)->toBe($expectedUnit);
    })->with([
        'width descriptor' => ['100w', 100.0, 'w'],
        'pixel density' => ['2x', 2.0, 'x'],
        'decimal width' => ['1.5w', 1.5, 'w'],
        'decimal density' => ['1.5x', 1.5, 'x'],
        'large width' => ['1920w', 1920.0, 'w'],
        'numeric input becomes width' => [100, 100.0, 'w'],
        'uppercase W' => ['200W', 200.0, 'w'],
        'uppercase X' => ['3X', 3.0, 'x'],
    ]);

    test('throws on invalid string sizes', function (string $input) {
        AssetsHelper::parseSrcsetSize($input);
    })->with([
        'invalid unit' => ['100p'],
        'text only' => ['large'],
        'empty string' => [''],
        'valid unit with trailing chars' => ['2xo'],
    ])->throws(InvalidArgumentException::class);

    test('treats numeric string as width descriptor', function () {
        [$value, $unit] = AssetsHelper::parseSrcsetSize('100');

        expect($value)->toBe(100.0);
        expect($unit)->toBe('w');
    });

    test('throws on non-string non-numeric input', function () {
        expect(fn () => AssetsHelper::parseSrcsetSize(['100w']))
            ->toThrow(InvalidArgumentException::class);
    });
});

describe('scaledDimensions', function () {
    test('scales dimensions correctly', function (array $real, array $max, array $expected) {
        [$width, $height] = AssetsHelper::scaledDimensions($real[0], $real[1], $max[0], $max[1]);

        expect($width)->toBe($expected[0]);
        expect($height)->toBe($expected[1]);
    })->with([
        'landscape into square' => [[800, 400], [200, 200], [200, 100]],
        'portrait into square' => [[400, 800], [200, 200], [100, 200]],
        'square into square' => [[800, 800], [200, 200], [200, 200]],
        'exact fit width' => [[400, 200], [400, 400], [400, 200]],
        'exact fit height' => [[200, 400], [400, 400], [200, 400]],
        'wide panorama' => [[2000, 100], [400, 400], [400, 20]],
        'tall narrow' => [[100, 2000], [400, 400], [20, 400]],
    ]);

    test('handles zero width', function () {
        [$width, $height] = AssetsHelper::scaledDimensions(0, 100, 200, 200);

        expect($width)->toBe(200);
        expect($height)->toBe(200);
    });

    test('handles zero height', function () {
        [$width, $height] = AssetsHelper::scaledDimensions(100, 0, 200, 200);

        expect($width)->toBe(200);
        expect($height)->toBe(200);
    });

    test('handles both dimensions zero', function () {
        [$width, $height] = AssetsHelper::scaledDimensions(0, 0, 200, 200);

        expect($width)->toBe(200);
        expect($height)->toBe(200);
    });
});

describe('fileTransferList', function () {
    test('builds transfer list from assets and folder changes', function () {
        $assets = [
            (object) ['id' => 1, 'folderId' => 10],
            (object) ['id' => 2, 'folderId' => 10],
            (object) ['id' => 3, 'folderId' => 20],
        ];

        $folderIdChanges = [
            10 => 100,
            20 => 200,
        ];

        $result = AssetsHelper::fileTransferList($assets, $folderIdChanges);

        expect($result)->toHaveCount(3);
        expect($result[0])->toBe(['assetId' => 1, 'folderId' => 100, 'force' => true]);
        expect($result[1])->toBe(['assetId' => 2, 'folderId' => 100, 'force' => true]);
        expect($result[2])->toBe(['assetId' => 3, 'folderId' => 200, 'force' => true]);
    });

    test('returns empty array when no assets provided', function () {
        expect(AssetsHelper::fileTransferList([], []))->toBe([]);
    });
});

describe('getMaxUploadSize', function () {
    test('returns exact config value when it is the smallest limit', function () {
        Cms::config()->maxUploadFileSize = 1;

        expect(AssetsHelper::getMaxUploadSize())->toBe(1);
    });

    test('respects config maxUploadFileSize limit', function () {
        Cms::config()->maxUploadFileSize = 1024;

        $result = AssetsHelper::getMaxUploadSize();
        expect($result)->toBeLessThanOrEqual(1024);
    });

    test('returns a positive number', function () {
        $result = AssetsHelper::getMaxUploadSize();

        expect($result)->toBeGreaterThan(0);
    });
});

describe('getFileKinds', function () {
    test('returns an array of file kinds', function () {
        $kinds = AssetsHelper::getFileKinds();

        expect($kinds)->toBeArray();
        expect($kinds)->not->toBeEmpty();
    });

    test('each kind has label and extensions', function () {
        $kinds = AssetsHelper::getFileKinds();

        foreach ($kinds as $info) {
            expect($info)->toHaveKey('label');
            expect($info)->toHaveKey('extensions');
            expect($info['extensions'])->toBeArray();
        }
    });

    test('contains expected kinds', function (string $kind) {
        $kinds = AssetsHelper::getFileKinds();

        expect($kinds)->toHaveKey($kind);
    })->with([
        'image' => [FileKind::Image->value],
        'audio' => [FileKind::Audio->value],
        'video' => [FileKind::Video->value],
        'pdf' => [FileKind::Pdf->value],
        'json' => [FileKind::Json->value],
        'xml' => [FileKind::Xml->value],
        'compressed' => [FileKind::Compressed->value],
        'excel' => [FileKind::Excel->value],
        'word' => [FileKind::Word->value],
        'powerpoint' => [FileKind::Powerpoint->value],
    ]);

    test('results are sorted by label', function () {
        $kinds = AssetsHelper::getFileKinds();
        $labels = array_column($kinds, 'label');

        $sorted = $labels;
        sort($sorted);

        expect($labels)->toBe($sorted);
    });

    test('it merges in extraFileKinds', function () {
        Cms::config()->extraFileKinds = [
            'stylesheet' => [
                'label' => 'Stylesheet',
                'extensions' => ['css', 'less', 'pcss', 'sass', 'scss', 'styl'],
            ],
        ];

        expect(AssetsHelper::getFileKinds())->toHaveKey('stylesheet');
    });

    test('it includes registered file kinds', function () {
        app(AssetFileKinds::class)->register('stylesheet', [
            'label' => 'Stylesheet',
            'extensions' => ['css'],
        ]);

        expect(AssetsHelper::getFileKinds())
            ->toHaveKey('stylesheet')
            ->and(AssetsHelper::getFileKinds()['stylesheet']['extensions'])->toBe(['css']);
    });
});

describe('getAllowedFileKinds', function () {
    test('returns a subset of all file kinds', function () {
        $all = AssetsHelper::getFileKinds();
        $allowed = AssetsHelper::getAllowedFileKinds();

        expect(count($allowed))->toBeLessThanOrEqual(count($all));

        foreach ($allowed as $kind => $info) {
            expect($all)->toHaveKey($kind);
        }
    });

    test('only includes kinds with at least one allowed extension', function () {
        $allowedExtensions = Cms::config()->allowedFileExtensions;
        $allowedKinds = AssetsHelper::getAllowedFileKinds();

        foreach ($allowedKinds as $kind => $info) {
            $hasAllowedExtension = false;
            foreach ($info['extensions'] as $extension) {
                if (in_array($extension, $allowedExtensions, true)) {
                    $hasAllowedExtension = true;

                    break;
                }
            }
            expect($hasAllowedExtension)->toBeTrue("Kind '{$kind}' should have at least one allowed extension");
        }
    });

    test('image kind is allowed by default', function () {
        $allowed = AssetsHelper::getAllowedFileKinds();

        expect($allowed)->toHaveKey(FileKind::Image->value);
    });
});

describe('iconSvg', function () {
    test('returns a string for valid extensions', function () {
        $result = AssetsHelper::iconSvg('pdf');

        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    });

    test('throws on invalid extension characters', function () {
        AssetsHelper::iconSvg('not/valid');
    })->throws(InvalidArgumentException::class);

    test('throws on empty extension', function () {
        AssetsHelper::iconSvg('');
    })->throws(InvalidArgumentException::class);

    test('handles various extension lengths', function (string $extension) {
        $result = AssetsHelper::iconSvg($extension);

        expect($result)->toBeString();
        expect($result)->not->toBeEmpty();
    })->with([
        'short (2 chars)' => ['ai'],
        'medium (3 chars)' => ['pdf'],
        'long (4 chars)' => ['docx'],
        'very long (5+ chars)' => ['pages'],
    ]);
});

describe('tempFilePath', function () {
    test('returns a path in the temp directory', function () {
        $path = AssetsHelper::tempFilePath();

        expect($path)->toContain(app(Path::class)->temp());
    });

    test('uses default tmp extension', function () {
        $path = AssetsHelper::tempFilePath();

        expect($path)->toEndWith('.tmp');
    });

    test('uses provided extension', function () {
        $path = AssetsHelper::tempFilePath('test');

        expect($path)->toEndWith('.test');
    });

    test('creates the temp file on disk', function () {
        $path = AssetsHelper::tempFilePath();

        expect(file_exists($path))->toBeTrue();

        // Cleanup
        @unlink($path);
    });
});

describe('INDEX_SKIP_ITEMS_PATTERN', function () {
    test('matches system files that should be skipped', function (string $path) {
        expect(preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $path))->toBe(1);
    })->with([
        'Thumbs.db' => ['Thumbs.db'],
        '__MACOSX' => ['__MACOSX'],
        '__MACOSX with trailing slash' => ['__MACOSX/'],
        '__MACOSX subdirectory' => ['__MACOSX/somefile'],
        '.DS_STORE' => ['.DS_STORE'],
        '.DS_STORE lowercase' => ['.ds_store'],
        'nested Thumbs.db' => ['folder/Thumbs.db'],
    ]);

    test('does not match regular files', function (string $path) {
        expect(preg_match(AssetsHelper::INDEX_SKIP_ITEMS_PATTERN, $path))->toBe(0);
    })->with([
        'regular jpg' => ['photo.jpg'],
        'regular txt' => ['document.txt'],
        'nested file' => ['folder/photo.jpg'],
    ]);
});
