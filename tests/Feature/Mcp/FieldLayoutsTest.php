<?php

declare(strict_types=1);

use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Mcp\Capabilities\Addresses;
use CraftCms\Cms\Mcp\Capabilities\Users;
use CraftCms\Cms\Mcp\Public\ElementQuery;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\ProjectConfig\ProjectConfig as ProjectConfigPaths;
use CraftCms\Cms\Support\Facades\ProjectConfig;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;

use function Pest\Laravel\actingAs;

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

it('selects user custom fields while retaining the public account attribute allowlist', function (): void {
    $actor = User::query()->firstOrFail();
    $actor->forceFill(['lastLoginDate' => '2026-10-01 12:00:00', 'invalidLoginCount' => 3])->save();
    actingAs($actor);
    app(Request::class)->setUserResolver(static fn (): User => $actor);
    $field = Field::factory()->create(['handle' => 'profileSummary', 'type' => PlainText::class]);
    $layout = FieldLayout::factory()->forField($field)->create();
    $users = app(Users::class);
    $users->updateFieldLayout($layout->config);
    $users->update(id: $actor->id, fields: ['profileSummary' => 'A public profile']);

    expect($users->list(['id' => $actor->id])->structuredContent['users'][0])->not->toHaveKey('profileSummary')
        ->and($users->list(['id' => $actor->id], fields: ['profileSummary'])->structuredContent['users'][0]['profileSummary'])->toBe('A public profile')
        ->and($users->get(id: $actor->id)['user']['profileSummary'])->toBe('A public profile');

    app()->instance(Settings::class, new Settings([
        'publicSiteHandles' => [Sites::getPrimarySite()->handle],
        'publicElementTypes' => ['user'],
    ]));
    $user = app(ElementQuery::class)->query('user', ['id' => $actor->id], fields: ['profileSummary', 'admin', 'lastLoginDate', 'invalidLoginCount'])['elements'][0];

    expect($user['profileSummary'])->toBe('A public profile')
        ->and($user)->not->toHaveKeys(['admin', 'lastLoginDate', 'invalidLoginCount']);
});
