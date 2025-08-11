<?php

arch()
    ->expect('cms')
    ->not->toUse(['die', 'dd', 'dump', 'env']);

/**
 * We only want our own Env helpers to be used.
 */
arch()
    ->expect(\Illuminate\Support\Env::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Support\Env::class);

arch()
    ->expect(\Illuminate\Support\Arr::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Support\Arr::class);

arch()
    ->expect(\Illuminate\Support\Str::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Support\Str::class);

arch()
    ->expect(\Illuminate\Support\Facades\Http::class)
    ->not
    ->toBeUsed()
    ->ignoring(\CraftCms\Cms\Support\Facades\Http::class);

arch('Only use JSON helper')
    ->expect(['json_encode', 'json_decode'])
    ->not
    ->toBeUsed()
    ->ignoring([
        \CraftCms\Cms\Support\Json::class,
        \craft\web\twig\Extension::class, // Depth argument needed
    ]);
