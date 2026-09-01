<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Cms;
use CraftCms\Cms\View\TemplateEngine;
use CraftCms\Cms\View\TemplateMode;
use Illuminate\Support\Facades\File;

use function CraftCms\Cms\backTraceAsString;
use function CraftCms\Cms\maxPowerCaptain;
use function CraftCms\Cms\normalizeValue;
use function CraftCms\Cms\normalizeVersion;
use function CraftCms\Cms\pageTemplate;
use function CraftCms\Cms\silence;
use function CraftCms\Cms\template;

test('normalizeVersion', function (string $expected, string $version) {
    expect(normalizeVersion($version))->toBe($expected);
})->with([
    ['21', 'version 21'],
    ['120.19.2', 'v120.19.2--beta'],
    ['', 'version'],
    ['2', '2\0\0'],
    ['2', '2+2+2'],
    ['2', '2-0-0'],
    ['', '~2'],
    ['', ''],
    ['', '\*v^2.0.0(beta)'],
    ['2.0.0-alpha', '2.0.0-alpha+foo'],
    ['2.0.0-alpha', '2.0.0-alpha.+foo'],
    ['2.0.0-alpha.10', '2.0.0-alpha.10+foo'],
    ['10.5.13', '5.5.5-10.5.13-MariaDB-1:10.5.13+maria~focal-log'],
    ['10.3.38', '10.3.38-MariaDB-1:10.3.38+maria~ubu2004-log'],
    ['5.5.5', '5.5.5-ubuntu-20.04'],
    ['10.3.38', '5.5.5-10.3.38-ubuntu-20.04'],
    ['5.7.16', '5.7.16-0ubuntu0.16.04.1'],
]);

test('normalizeValue', function (mixed $expected, mixed $value) {
    expect(normalizeValue($value))->toBe($expected);
})->with([
    [true, 'true'],
    [true, 'TRUE'],
    [false, 'false'],
    [false, 'FALSE'],
    [123, '123'],
    ['123 ', '123 '],
    [' 123', ' 123'],
    [123.4, '123.4'],
    ['foo', 'foo'],
    [null, null],
    ['2833563543.1341693581393', '2833563543.1341693581393'], // https://github.com/craftcms/cms/issues/15533
]);

test('maxPowerCaptain', function () {
    $oldMemoryLimit = ini_get('memory_limit');
    $oldMaxExecution = ini_get('max_execution_time');

    $generalConfig = Cms::config();
    $generalConfig->phpMaxMemoryLimit = '512M';

    if (@ini_set('memory_limit', '256M') === false) {
        $this->markTestSkipped('Unable to set memory_limit');
    }

    maxPowerCaptain();

    expect(ini_get('memory_limit'))->toBe($generalConfig->phpMaxMemoryLimit);
    expect(ini_get('max_execution_time'))->toBe('0');

    ini_set('memory_limit', $oldMemoryLimit);
    ini_set('max_execution_time', $oldMaxExecution);
});

test('silence', function () {
    expect(silence(fn () => 'foo'))->toBe('foo');
    expect(silence(function () {}))->toBeNull();
    expect(silence(function (): void {}))->toBeNull();
});

test('backtraceAsString', function () {
    expect(backtraceAsString())->toBeString();
    expect(backtraceAsString())->toContain('backtraceAsString');
});

describe('template helpers', function () {
    beforeEach(function () {
        $this->tempDir = sys_get_temp_dir().'/craft-template-helper-test-'.uniqid();
        File::ensureDirectoryExists($this->tempDir);
        Aliases::set('@templates', $this->tempDir);
        TemplateMode::set(TemplateMode::Site);
        app()->forgetScopedInstances();
    });

    afterEach(function () {
        File::deleteDirectory($this->tempDir);
    });

    test('template renders Blade templates through the neutral renderer', function () {
        File::put($this->tempDir.'/helper.blade.php', 'Blade {{ $name }}');

        expect(template('helper', ['name' => 'Craft']))->toBe('Blade Craft');
    });

    test('pageTemplate renders Blade page templates with an explicit renderer', function () {
        File::put($this->tempDir.'/page.blade.php', <<<'BLADE'
<html>
<head>@craftHead</head>
<body>
@craftEndBody
@craftJs('console.log("helper");')
Page {{ $name }}
</body>
</html>
BLADE);

        expect(pageTemplate('page', ['name' => 'Craft'], renderer: TemplateEngine::Blade))
            ->toContain('Page Craft')
            ->toContain('console.log("helper");')
            ->not->toContain('CRAFT-BLOCK-BODY-END');
    });
});
