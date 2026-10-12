<?php

declare(strict_types=1);

use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\FieldLayout\LayoutElements\Entries\EntryTitleField;
use CraftCms\Cms\FieldLayout\NativeFields;

it('applies native field providers', function () {
    $registry = app(NativeFields::class);
    $registry->register('plugin', fn (FieldLayout $layout, array $fields) => [...$fields, EntryTitleField::class]);

    expect($registry->apply(new FieldLayout))->toBe([EntryTitleField::class]);
});

it('resolves provider dependencies from the current scope with contextual arguments', function () {
    app()->scoped(EntryTitleField::class, fn () => new EntryTitleField);

    $registry = app(NativeFields::class);
    $registry->remove('craft');
    $layout = new FieldLayout;
    $calls = [];
    $registry->register('plugin', function (FieldLayout $fieldLayout, array $fields, EntryTitleField $titleField) use (&$calls): array {
        $calls[] = [$fieldLayout, $fields];

        return [...$fields, $titleField];
    });

    $first = $registry->apply($layout, [EntryTitleField::class]);
    app()->forgetScopedInstances();
    $second = $registry->apply($layout, [EntryTitleField::class]);

    expect($calls)->toBe([
        [$layout, [EntryTitleField::class]],
        [$layout, [EntryTitleField::class]],
    ])->and($first[1])->not()->toBe($second[1]);
});

it('rejects duplicate provider handles', function () {
    $registry = app(NativeFields::class);
    $registry->register('plugin', fn (FieldLayout $layout, array $fields) => $fields);

    expect(fn () => $registry->register('plugin', fn (FieldLayout $layout, array $fields) => $fields))
        ->toThrow(InvalidArgumentException::class);
});
