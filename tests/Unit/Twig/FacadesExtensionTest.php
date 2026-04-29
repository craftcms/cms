<?php

declare(strict_types=1);

use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Twig\Extensions\LaravelExtension;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\Facades\Facade;

function craftFacadeClasses(): array
{
    return collect(glob(getcwd().'/src/Support/Facades/*.php') ?: [])
        ->mapWithKeys(function (string $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            return [$name => "CraftCms\\Cms\\Support\\Facades\\$name"];
        })
        ->sortKeys()
        ->all();
}

function composerLaravelAliases(): array
{
    $composer = file_get_contents(getcwd().'/composer.json');

    if ($composer === false) {
        return [];
    }

    return Json::decode($composer)['extra']['laravel']['aliases'] ?? [];
}

it('registers every Craft facade as a Laravel alias', function () {
    $aliases = composerLaravelAliases();
    $facades = craftFacadeClasses();

    $this->assertSame($facades, array_intersect_key($aliases, $facades));

    foreach ($aliases as $class) {
        $this->assertTrue(is_subclass_of($class, Facade::class));
    }
});

it('exposes Laravel facade aliases as Twig globals', function () {
    AliasLoader::getInstance(composerLaravelAliases());

    $globals = (new LaravelExtension)->getGlobals();

    $this->assertEmpty(array_diff(array_keys(composerLaravelAliases()), array_keys($globals)));
});
