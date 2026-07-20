<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Assets;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\RouteToken\RouteTokens;
use CraftCms\Cms\User\UserPermissions;
use CraftCms\Cms\User\Users;
use Illuminate\Container\Attributes\Scoped;

it('binds operation-local services as scoped', function (string $class) {
    expect(new ReflectionClass($class)->getAttributes(Scoped::class))->toHaveCount(1);
})->with([
    'assets' => [Assets::class],
    'GQL execution' => [Gql::class],
    'route tokens' => [RouteTokens::class],
    'users' => [Users::class],
    'user permissions' => [UserPermissions::class],
]);
