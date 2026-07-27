<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\NativeFields;
use CraftCms\Cms\Site\Sites;

it('applies native field providers', function () {
    $registry = app(NativeFields::class);
    $registry->register('plugin', fn (FieldLayout $layout, array $fields) => [...$fields, EntryTitleField::class]);

    expect($registry->apply(new FieldLayout))->toBe([EntryTitleField::class]);
});

it('resolves provider dependencies from the current scope with contextual arguments', function () {
    app()->scoped(Sites::class, fn () => Mockery::mock(Sites::class));

    $registry = app(NativeFields::class);
    $registry->remove('craft');
    $layout = new FieldLayout;
    $calls = [];
    $registry->register('plugin', function (FieldLayout $fieldLayout, array $fields, Sites $sites) use (&$calls): array {
        $calls[] = [$fieldLayout, $fields];

        return [...$fields, $sites];
    });

    $first = $registry->apply($layout, ['existing']);
    app()->forgetScopedInstances();
    $second = $registry->apply($layout, ['existing']);

    expect($calls)->toBe([
        [$layout, ['existing']],
        [$layout, ['existing']],
    ])->and($first[1])->not()->toBe($second[1]);
});

it('rejects duplicate provider handles', function () {
    $registry = app(NativeFields::class);
    $registry->register('plugin', fn (FieldLayout $layout, array $fields) => $fields);

    expect(fn () => $registry->register('plugin', fn (FieldLayout $layout, array $fields) => $fields))
        ->toThrow(InvalidArgumentException::class);
});
