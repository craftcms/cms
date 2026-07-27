<?php

declare(strict_types=1);

use CraftCms\Cms\User\Data\PermissionGroup;
use CraftCms\Cms\User\PermissionGroupCatalog;

it('adds permission groups lazily in registration order', function () {
    $registry = app(PermissionGroupCatalog::class);
    $resolved = false;
    $registry->register('plugin:first', function (PermissionGroupCatalog $registry) use (&$resolved) {
        $resolved = $registry === app(PermissionGroupCatalog::class);

        return new PermissionGroup('plugin:first', 'First');
    });
    $registry->register('plugin:second', fn () => new PermissionGroup('plugin:second', 'Second'));

    expect($resolved)->toBeFalse()
        ->and($registry->apply(collect([new PermissionGroup('core', 'Core')]))->pluck('heading')->all())
        ->toBe(['Core', 'First', 'Second'])
        ->and($resolved)->toBeTrue();
});

it('does not add null permission group contributions', function () {
    $registry = app(PermissionGroupCatalog::class);
    $registry->register('plugin:empty', fn () => null);

    expect($registry->apply(collect()))->toBeEmpty();
});

it('removes permission group contributions by handle', function () {
    $registry = app(PermissionGroupCatalog::class);
    $registry->register('plugin:before', fn () => new PermissionGroup('plugin:before', 'Before'));
    $registry->register('plugin:removed', fn () => new PermissionGroup('plugin:removed', 'Removed'));
    $registry->register('plugin:after', fn () => new PermissionGroup('plugin:after', 'After'));
    $registry->remove('plugin:removed', 'plugin:missing');

    expect($registry->apply(collect())->pluck('handle'))
        ->toContain('plugin:before', 'plugin:after')
        ->not()->toContain('plugin:removed');
});

it('rejects duplicate permission group handles', function () {
    $registry = app(PermissionGroupCatalog::class);
    $registry->register('plugin:test', fn () => new PermissionGroup('plugin:test', 'Plugin'));

    $registry->register('plugin:test', fn () => new PermissionGroup('plugin:test', 'Plugin'));
})->throws(InvalidArgumentException::class, 'Permission group [plugin:test] is already registered.');

it('rejects invalid permission group factory results', function () {
    app(PermissionGroupCatalog::class)->register('plugin:test', fn () => 'invalid');

    app(PermissionGroupCatalog::class)->apply(collect());
})->throws(InvalidArgumentException::class, 'Permission group factory [plugin:test] must return a permission group.');

it('rejects permission group factories with a different handle', function () {
    app(PermissionGroupCatalog::class)->register('plugin:test', fn () => new PermissionGroup('plugin:other', 'Plugin'));

    app(PermissionGroupCatalog::class)->apply(collect());
})->throws(InvalidArgumentException::class, 'Permission group factory [plugin:test] returned group handle [plugin:other].');

it('rejects duplicate resolved permission group handles', function () {
    app(PermissionGroupCatalog::class)->register('plugin:test', fn () => new PermissionGroup('plugin:test', 'Plugin'));

    app(PermissionGroupCatalog::class)->apply(collect([new PermissionGroup('plugin:test', 'Existing plugin')]));
})->throws(InvalidArgumentException::class, 'Permission group handle [plugin:test] is duplicated.');
