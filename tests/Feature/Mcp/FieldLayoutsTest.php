<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\Capabilities\Addresses;
use CraftCms\Cms\Mcp\Capabilities\Users;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigPaths;
use CraftCms\Cms\Support\Facades\ProjectConfig;

it('manages user and address field layouts through their owners', function () {
    $userLayout = app(Users::class)->updateFieldLayout([
        'tabs' => [['name' => 'User content', 'elements' => []]],
    ])['fieldLayout'];
    $addressLayout = app(Addresses::class)->updateFieldLayout([
        'tabs' => [['name' => 'Address content', 'elements' => []]],
    ])['fieldLayout'];
    $fetchedUserLayout = app(Users::class)->getFieldLayout()['fieldLayout'];
    $fetchedAddressLayout = app(Addresses::class)->getFieldLayout()['fieldLayout'];
    $userConfig = ProjectConfig::get(sprintf('%s.%s', ProjectConfigPaths::PATH_USER_FIELD_LAYOUTS, $userLayout['uid']));
    $addressConfig = ProjectConfig::get(sprintf('%s.%s', ProjectConfigPaths::PATH_ADDRESS_FIELD_LAYOUTS, $addressLayout['uid']));

    expect($userLayout['config']['tabs'][0]['name'])->toBe('User content')
        ->and($fetchedUserLayout['uid'])->toBe($userLayout['uid'])
        ->and($fetchedUserLayout['config']['tabs'][0]['name'])->toBe('User content')
        ->and($userConfig['tabs'][0]['name'])->toBe('User content')
        ->and($addressLayout['config']['tabs'][0]['name'])->toBe('Address content')
        ->and($fetchedAddressLayout['uid'])->toBe($addressLayout['uid'])
        ->and($fetchedAddressLayout['config']['tabs'][0]['name'])->toBe('Address content')
        ->and($addressConfig['tabs'][0]['name'])->toBe('Address content');
});
