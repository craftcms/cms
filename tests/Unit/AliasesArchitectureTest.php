<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

it('keeps path alias dependencies in the yii2 adapter', function () {
    $packagePath = dirname(__DIR__, 2);
    $references = ['CraftCms\\'.'Aliases', 'Yiisoft\\'.'Aliases'];
    $files = collect([
        "$packagePath/composer.json",
        "$packagePath/testbench.yaml",
        ...File::allFiles("$packagePath/src"),
        ...File::allFiles("$packagePath/packages"),
        ...File::allFiles("$packagePath/scripts"),
        ...File::allFiles("$packagePath/tests"),
        ...File::allFiles("$packagePath/cms-assets"),
    ]);

    $matches = $files->filter(function (SplFileInfo|string $file) use ($references): bool {
        if (! in_array(pathinfo((string) $file, PATHINFO_EXTENSION), ['json', 'php', 'yaml'], true)) {
            return false;
        }

        $contents = File::get((string) $file);

        return collect($references)->contains(fn (string $reference): bool => str_contains($contents, $reference));
    });

    expect($matches->map(fn (SplFileInfo|string $file): string => (string) $file)->values()->all())->toBeEmpty();
});
