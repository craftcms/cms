<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Composer;
use CraftCms\Cms\Support\File;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

beforeEach(function () {
    $this->composer = app(Composer::class);
});

it('can get the composer.json path', function () {
    expect($this->composer->getJsonPath())->toBe(File::normalizePath(base_path('composer.json')));
});

it('throws a FileNotFoundException when composer.json does not exist', function () {
    Aliases::set('@root/composer.json', __DIR__.'/composer.json');

    expect($this->composer->getJsonPath());
})->throws(FileNotFoundException::class);

it('can get the composer.lock path', function () {
    touch(base_path('composer.lock'));

    expect($this->composer->getLockPath())->toBe(File::normalizePath(base_path('composer.lock')));

    unlink(base_path('composer.lock'));
});

it('can get the composer config', function () {
    $config = $this->composer->getConfig();

    expect($config['name'])->toBe('laravel/laravel');
});

it('can sort packages', function () {
    $packages = [
        'craftcms/cms' => '4.5.5',
        'craftcms/contact-form' => '3.0.1',
        'vlucas/phpdotenv' => '^5.5.0',
        'php' => '~8.2.0',
        'verbb/smith' => '2.0.0',
        'clubstudioltd/craft-asset-rev' => '7.0.0',
        'craftcms/ckeditor' => '3.6.0',
        'verbb/navigation' => '2.0.21',
        'verbb/super-table' => '3.0.9',
        'rynpsc/craft-phone-number' => '2.1.0',
        'sebastianlenz/linkfield' => '2.1.5',
        'twig/string-extra' => '^3.5',
    ];

    $expected = [
        'php' => '~8.2.0',
        'clubstudioltd/craft-asset-rev' => '7.0.0',
        'craftcms/ckeditor' => '3.6.0',
        'craftcms/cms' => '4.5.5',
        'craftcms/contact-form' => '3.0.1',
        'rynpsc/craft-phone-number' => '2.1.0',
        'sebastianlenz/linkfield' => '2.1.5',
        'twig/string-extra' => '^3.5',
        'verbb/navigation' => '2.0.21',
        'verbb/smith' => '2.0.0',
        'verbb/super-table' => '3.0.9',
        'vlucas/phpdotenv' => '^5.5.0',
    ];

    $this->composer->sortPackages($packages);

    expect($packages)->toBe($expected);
});

it('leaves composer.json untouched when preparation fails', function () {
    $path = tempnam(sys_get_temp_dir(), 'craft-composer-');
    $original = "{\n\t\"require\": \"invalid\"\n}\n";
    File::put($path, $original);
    Aliases::set('@root/composer.json', $path);

    try {
        expect(fn () => $this->composer->install(['vendor/package' => '^1.0']))->toThrow(TypeError::class);
        expect(File::get($path))->toBe($original);
    } finally {
        File::delete($path);
    }
});

describe('system Composer', function () {
    beforeEach(function () {
        $this->originalPath = getenv('PATH');
        $this->directory = sys_get_temp_dir().'/craft-composer-'.bin2hex(random_bytes(8));
        File::ensureDirectoryExists($this->directory);
        File::put($this->directory.'/version', 'Composer version 2.2.0 2021-12-22');
        File::put($this->directory.'/exit-code', '0');
        File::put($this->directory.'/composer', '#!'.PHP_BINARY."\n".<<<'PHP'
            <?php
            if (in_array('--version', $argv, true)) {
                if (!in_array('--no-plugins', $argv, true) || !in_array('--no-scripts', $argv, true)) {
                    exit(1);
                }
                file_put_contents(__DIR__.'/probes', 'probe\n', FILE_APPEND);
                echo file_get_contents(__DIR__.'/version');
                exit((int) file_get_contents(__DIR__.'/exit-code'));
            }
            file_put_contents(__DIR__.'/command', implode("\n", array_slice($argv, 1)));
            echo "Packages installed\n";
            PHP);
        chmod($this->directory.'/composer', 0755);
        putenv('PATH='.$this->directory);
        $this->composer = new Composer;
    });

    afterEach(function () {
        putenv('PATH='.$this->originalPath);
        File::deleteDirectory($this->directory);
    });

    it('only enables compatible working Composer executables', function (?string $version, int $exitCode, bool $available) {
        if ($version === null) {
            File::delete($this->directory.'/composer');
        } else {
            File::put($this->directory.'/version', $version);
            File::put($this->directory.'/exit-code', (string) $exitCode);
        }

        expect($this->composer->getIsAvailable())->toBe($available)
            ->and($this->composer->getUnavailableReason() === null)->toBe($available);
    })->with([
        'missing' => [null, 0, false],
        'Composer 1' => ['Composer version 1.10.27', 0, false],
        'below minimum' => ['Composer version 2.1.14', 0, false],
        'minimum prerelease' => ['Composer version 2.2.0-RC1', 0, false],
        'minimum' => ['Composer version 2.2.0 2021-12-22', 0, true],
        'current' => ['Composer version 2.11.1 2026-10-30', 0, true],
        'failed probe' => ['Composer version 2.11.1', 1, false],
        'unrecognized output' => ['not Composer', 0, false],
    ]);

    it('probes only once per service instance', function () {
        expect($this->composer->getIsAvailable())->toBeTrue();
        File::put($this->directory.'/version', 'Composer version 1.10.27');

        expect($this->composer->getIsAvailable())->toBeTrue()
            ->and(File::get($this->directory.'/probes'))->toBe('probe\n')
            ->and(new Composer()->getIsAvailable())->toBeFalse();
    });

    it('caches UI availability across request scopes for 60 seconds without caching operational checks', function (bool $available) {
        File::put($this->directory.'/version', $available ? 'Composer version 2.2.0' : 'Composer version 1.10.27');
        app()->forgetInstance(Composer::class);

        expect(app(Composer::class)->getIsAvailableForUi())->toBe($available);
        File::put($this->directory.'/version', $available ? 'Composer version 1.10.27' : 'Composer version 2.2.0');
        app()->forgetScopedInstances();

        expect(app(Composer::class)->getIsAvailableForUi())->toBe($available)
            ->and(app(Composer::class)->getUnavailableReason(forUi: true) === null)->toBe($available)
            ->and(File::get($this->directory.'/probes'))->toBe('probe\n')
            ->and(app(Composer::class)->getIsAvailable())->toBe(! $available);

        $this->travel(61)->seconds();
        app()->forgetScopedInstances();

        expect(app(Composer::class)->getIsAvailableForUi())->toBe(! $available);
    })->with([true, false]);

    it('leaves project files untouched when Composer is unavailable', function (string $operation) {
        File::delete($this->directory.'/composer');
        $jsonPath = $this->directory.'/composer.json';
        $lockPath = $this->directory.'/composer.lock';
        File::put($jsonPath, "{\n  \"require\": {}\n}\n");
        File::put($lockPath, '{}');
        Aliases::set('@root/composer.json', $jsonPath);

        $requirements = $operation === 'install' ? ['vendor/package' => '^1.0'] : ['vendor/package'];

        expect(fn () => $this->composer->$operation($requirements))
            ->toThrow(RuntimeException::class, 'Composer 2.2.0 or later must be installed and available in the PATH environment variable.');
        expect(File::get($jsonPath))->toBe("{\n  \"require\": {}\n}\n")
            ->and(File::get($lockPath))->toBe('{}');
    })->with(['install', 'uninstall']);

    it('runs the system executable with its arguments and streams output', function (bool $wrapper) {
        if ($wrapper) {
            File::move($this->directory.'/composer', $this->directory.'/composer.php');
            File::put($this->directory.'/composer', "#!/bin/sh\nexec ".escapeshellarg(PHP_BINARY).' '.escapeshellarg($this->directory.'/composer.php').' "$@"');
            chmod($this->directory.'/composer', 0755);
        }

        $jsonPath = $this->directory.'/composer.json';
        File::put($jsonPath, '{"require":{}}');
        Aliases::set('@root/composer.json', $jsonPath);
        $output = '';

        $this->composer->install(callback: function ($type, $buffer) use (&$output) {
            $output .= $buffer;
        });

        expect($output)->toContain('Packages installed')
            ->and(File::get($this->directory.'/command'))->toBe(implode("\n", [
                'install', '--working-dir', $this->directory, '--no-ansi', '--no-interaction',
            ]));
    })->with(['PHP executable' => false, 'shell wrapper' => true]);
})->skip(PHP_OS_FAMILY === 'Windows', 'Executable fixtures use Unix shebangs.');
