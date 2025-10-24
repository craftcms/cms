<?php

declare(strict_types=1);

use craft\web\twig\Extension;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Arr;
use Illuminate\Support\Env;
use Illuminate\Support\Str;

arch()
    ->expect('cms')
    ->not->toUse(['die', 'dd', 'dump', 'env']);

/**
 * We only want our own Env helpers to be used.
 */
arch()
    ->expect(Env::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Support\Env::class);

arch()
    ->expect(Arr::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Support\Arr::class);

arch()
    ->expect(Str::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Support\Str::class);

arch('Only use JSON helper')
    ->expect(['json_encode', 'json_decode'])
    ->not
    ->toBeUsed()
    ->ignoring([
        Json::class,
        Extension::class, // Depth argument needed
    ]);

arch('Don\'t use default migrator')
    ->expect(\Illuminate\Database\Migrations\Migrator::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Database\Migrator::class);
