<?php

declare(strict_types=1);

use CraftCms\Aliases\Aliases;
use CraftCms\Cms\Support\Composer;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

beforeEach(function () {
    $this->composer = resolve(Composer::class);
});

it('can get the composer.json path', function () {
    expect($this->composer->getJsonPath())->toBe(base_path('composer.json'));
});

it('throws a FileNotFoundException when composer.json does not exist', function () {
    Aliases::set('@root/composer.json', __DIR__.'/composer.json');

    expect($this->composer->getJsonPath());
})->throws(FileNotFoundException::class);

it('can get the composer.lock path', function () {
    expect($this->composer->getLockPath())->toBeNull();

    touch(base_path('composer.lock'));

    expect($this->composer->getLockPath())->toBe(base_path('composer.lock'));

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
