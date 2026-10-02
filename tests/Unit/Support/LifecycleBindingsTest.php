<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\User\Users;
use CraftCms\Cms\View\DeltaRegistry;
use CraftCms\Cms\View\HtmlStack;
use CraftCms\Cms\View\InputNamespace;
use CraftCms\Cms\View\PageLifecycle;
use CraftCms\Cms\View\TemplateHooks;

it('binds operation-local services as scoped', function (string $class) {
    $first = app($class);

    expect(app($class))->toBe($first);

    app()->forgetScopedInstances();

    expect(app($class))->not->toBe($first);
})->with([
    'assets' => [Assets::class],
    'GQL execution' => [Gql::class],
    'route tokens' => [RouteTokens::class],
    'users' => [Users::class],
    'user permissions' => [UserPermissions::class],
    'delta registry' => [DeltaRegistry::class],
    'HTML stack' => [HtmlStack::class],
    'input namespace' => [InputNamespace::class],
    'page lifecycle' => [PageLifecycle::class],
    'template hooks' => [TemplateHooks::class],
]);
