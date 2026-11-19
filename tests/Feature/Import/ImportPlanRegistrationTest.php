<?php

declare(strict_types=1);

use CraftCms\Cms\Import\Data\ImportPlan as ImportPlanData;
use CraftCms\Cms\Import\ImportPlans;

it('doesn’t create a registered import plan until the import plans are loaded', function () {
    $calls = 0;

    app(ImportPlans::class)->register('lazyImport', function () use (&$calls) {
        $calls++;

        return new ImportPlanData()->name('Lazy Import');
    });

    expect($calls)->toBe(0);

    app(ImportPlans::class)->getAllImportPlans();

    expect($calls)->toBe(1);
});

it('rejects registering the same import plan handle twice', function () {
    $imports = app(ImportPlans::class);

    $imports->register('twice', fn () => new ImportPlanData);

    expect(fn () => $imports->register('twice', fn () => new ImportPlanData))
        ->toThrow(InvalidArgumentException::class);
});

it('excludes a registered import plan whose handle doesn’t match its registered one', function () {
    $imports = app(ImportPlans::class);

    $imports->register('registered', fn () => new ImportPlanData()->name('Mismatched')->handle('other'));

    expect($imports->getAllImportPlans()->has('registered'))->toBeFalse()
        ->and($imports->getAllImportPlans()->has('other'))->toBeFalse();
});
