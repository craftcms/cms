<?php

declare(strict_types=1);

use craft\web\twig\Extension;
use CraftCms\Cms\Database\Migrator;
use CraftCms\Cms\Support\File;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Illuminate\Support\Str;

arch('No debug functions')
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect('src')
    ->not->toUse(['die', 'dd', 'dump', 'env']);

arch('Don\'t use legacy logging')
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(['Craft::info', 'Craft::warning', 'Craft::debug', 'Craft::error', 'Craft::trace'])
    ->not()
    ->toBeUsed();

arch('Don\'t use legacy translations')
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect('Craft::t')
    ->not()
    ->toBeUsed();

/**
 * We only want our own Env helpers to be used.
 */
arch()
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(Env::class)
    ->not
    ->toBeUsed()
    ->ignoring(CraftCms\Cms\Support\Env::class);

arch()
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(Arr::class)
    ->not
    ->toBeUsedIn('src')
    ->ignoring(CraftCms\Cms\Support\Arr::class);

arch()
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(Illuminate\Support\Facades\File::class)
    ->not
    ->toBeUsedIn('src')
    ->ignoring(File::class);

arch()
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(Str::class)
    ->not
    ->toBeUsedIn('src')
    ->ignoring(CraftCms\Cms\Support\Str::class);

arch('Only use JSON helper')
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(['json_encode', 'json_decode'])
    ->not
    ->toBeUsed()
    ->ignoring([
        Json::class,
        Extension::class, // Depth argument needed
    ]);

arch('Don\'t use default migrator')
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(Illuminate\Database\Migrations\Migrator::class)
    ->not
    ->toBeUsed()
    ->ignoring(Migrator::class);

arch('Don\'t use legacy aliases')
    ->skip(fn () => getenv('SKIP_ARCH'))
    ->expect(['Craft::getAlias', 'Craft::setAlias', 'Craft::getRootAlias'])
    ->not()
    ->toBeUsed();
