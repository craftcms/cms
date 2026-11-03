<?php

declare(strict_types=1);

use CraftCms\Cms\Cp\CustomNavigations;
use CraftCms\Cms\Cp\Data\NavItem;
use CraftCms\Cms\Cp\Navigation;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Facades\UserGroups;
use CraftCms\Cms\Support\Facades\UserPermissions;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Conditions\GroupConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Models\UserGroup;
use Inertia\Testing\AssertableInertia;

use function CraftCms\Cms\cp_url;
use function CraftCms\Cms\currentUser;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

/** The labels at the top of the current user's nav, groups flattened. */
function topNavLabels(): array
{
    return collect(app(Navigation::class)->getTree())
        ->flatMap(fn (NavItem $item) => $item->group ? [$item->label, ...array_column($item->subnav, 'label')] : [$item->label])
        ->all();
}

beforeEach(function () {
    Edition::set(Edition::Pro);
});

it('saves the default navigation from the builder, and resets it', function () {
    actingAs(UserModel::factory()->admin()->createElement());

    $tree = get(cp_url('settings/cp-nav/default'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page->component('settings/cp-nav/Edit'))
        ->viewData('page')['props']['tree'];

    $tree[0]['hidden'] = true;
    $tree[0]['label'] = 'Home';

    $this->put(cp_url('settings/cp-nav/default'), ['nav' => $tree])
        ->assertRedirect(cp_url('settings/cp-nav/default'));

    expect(app(CustomNavigations::class)->getDefault())
        ->toEqual(['items' => ['nav-dashboard' => ['label' => 'Home', 'hidden' => true]]])
        ->and(topNavLabels())->not->toContain('Dashboard', 'Home');

    $this->put(cp_url('settings/cp-nav/default'), ['nav' => []]);

    expect(topNavLabels())->toContain('Dashboard');
});

it('only saves links to control panel paths and web, mail and phone URLs', function (string $url, bool $valid) {
    actingAs(UserModel::factory()->admin()->createElement());
    $link = ['id' => 'custom-link00', 'type' => 'link', 'label' => 'Link', 'url' => $url];

    $response = $this->put(cp_url('settings/cp-nav/default'), ['nav' => [
        $link,
        ['id' => 'custom-sect00', 'type' => 'section', 'label' => 'Section', 'children' => [
            [...$link, 'id' => 'custom-link01'],
        ]],
    ]]);

    if ($valid) {
        $response->assertSessionHasNoErrors();
        expect(app(CustomNavigations::class)->getDefault()['items'])->toHaveKeys(['custom-link00', 'custom-link01']);
    } else {
        $response->assertSessionHasErrors(['nav.0.url', 'nav.1.children.0.url']);
        expect(app(CustomNavigations::class)->getDefault())->toBe([]);
    }
})->with([
    'cp path' => ['entries/blog', true],
    'root-relative' => ['/admin/entries/blog', true],
    'https' => ['https://example.com', true],
    'mailto' => ['mailto:hi@example.com', true],
    'tel' => ['tel:+3212345678', true],
    'javascript' => ['javascript:alert(1)', false],
    'data' => ['data:text/html,hi', false],
    'protocol-relative' => ['//evil.example.com', false],
    'backslash protocol-relative' => ['/\\evil.example.com', false],
]);

it('builds a link’s settings in a slideout, and checks them before they’re applied', function () {
    actingAs(UserModel::factory()->admin()->createElement());
    $node = ['kind' => 'item', 'source' => 'custom', 'type' => 'link', 'label' => 'Docs', 'url' => 'https://docs.example.com', 'newWindow' => true, 'hidden' => false];

    $ui = $this->postJson(cp_url('settings/cp-nav/item-settings'), ['node' => $node])
        ->assertOk()
        ->json('ui');

    expect($ui['values'])->toMatchArray(['label' => 'Docs', 'url' => 'https://docs.example.com', 'newWindow' => true]);

    $this->postJson(cp_url('settings/cp-nav/item-settings/apply'), ['node' => $node, 'settings' => ['label' => 'Docs', 'url' => 'javascript:alert(1)']])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['settings.url' => 'Enter a control panel path'])
        ->assertJsonMissingValidationErrors(['settings.label']);

    $this->postJson(cp_url('settings/cp-nav/item-settings/apply'), ['node' => $node, 'settings' => ['label' => 'Docs', 'url' => 'entries/blog']])
        ->assertNoContent();
});

it('gives users the first matching group navigation, or the default navigation', function () {
    $navigations = app(CustomNavigations::class);
    $editors = UserGroup::factory()->create();
    $writers = UserGroup::factory()->create();
    $user = UserModel::factory()->admin()->createElement();
    $editors->users()->attach($user->id);
    $writers->users()->attach($user->id);

    $navigations->saveDefault(['items' => ['nav-dashboard' => ['label' => 'Default home']]]);
    $navigations->saveGroupNavigation(UserGroups::getGroupByUid($writers->uid), ['items' => ['nav-dashboard' => ['label' => 'Writers home']]]);
    $navigations->saveGroupNavigation(UserGroups::getGroupByUid($editors->uid), ['items' => ['nav-dashboard' => ['label' => 'Editors home'], 'nav-plugin-store' => ['hidden' => true]]]);
    actingAs($user);

    expect(topNavLabels())->toContain('Writers home')->toContain('Plugin Store');

    $navigations->reorderGroupNavigations([$editors->uid, $writers->uid]);

    expect(topNavLabels())->toContain('Editors home')->not->toContain('Plugin Store');

    Edition::set(Edition::Team);

    expect(topNavLabels())->toContain('Default home')->toContain('Plugin Store');

    Edition::set(Edition::Solo);

    expect(topNavLabels())->toContain('Dashboard');
});

it('lays out a group’s navigation from what the group can see, and saves only that', function () {
    $admin = UserModel::factory()->admin()->createElement();
    $group = UserGroup::factory()->create(['name' => 'Editors']);
    UserPermissions::saveGroupPermissions($group->id, ['accessCp', 'viewUsers']);
    actingAs($admin);

    $labels = fn (array $tree): array => collect($tree)
        ->flatMap(fn (array $node) => [$node['label'], ...array_column($node['children'], 'label')])
        ->all();
    $adminTree = get(cp_url('settings/cp-nav/default'))->viewData('page')['props']['tree'];
    $groupTree = get(cp_url('settings/cp-nav/'.$group->uid))
        ->assertOk()
        ->viewData('page')['props']['tree'];

    expect($labels($adminTree))->toContain('Users', 'Settings')
        ->and($labels($groupTree))->toContain('Users')->not->toContain('Settings', 'Plugin Store')
        ->and(currentUser()->getCraftUserId())->toBe($admin->id);

    // Hiding Settings means nothing to a group that can't see it.
    $administration = collect($adminTree)->firstWhere('label', 'Administration');
    $settingsId = collect($administration['children'])->firstWhere('label', 'Settings')['id'];
    $usersId = collect($administration['children'])->firstWhere('label', 'Users')['id'];
    $tree = collect($adminTree)->map(fn (array $node) => $node['label'] === 'Administration'
        ? [...$node, 'children' => array_map(fn (array $child) => [...$child, 'hidden' => true], $node['children'])]
        : $node)->all();

    $this->put(cp_url('settings/cp-nav/'.$group->uid), ['nav' => $tree])
        ->assertRedirect(cp_url('settings/cp-nav/'.$group->uid));

    expect(array_keys(app(CustomNavigations::class)->getGroupNavigation($group->uid)['nav']['items']))
        ->toContain($usersId)
        ->not->toContain($settingsId);
});

it('lists group navigations in the order they apply, with the default navigation last', function () {
    actingAs(UserModel::factory()->admin()->createElement());
    $navigations = app(CustomNavigations::class);
    $editors = UserGroups::getGroupByUid(UserGroup::factory()->create(['name' => 'Editors'])->uid);
    $contractors = UserGroups::getGroupByUid(UserGroup::factory()->create(['name' => 'Contractors'])->uid);
    $navigations->saveGroupNavigation($editors, ['items' => ['nav-plugin-store' => ['hidden' => true]]]);
    $navigations->saveGroupNavigation($contractors, []);
    $navigations->reorderGroupNavigations([$contractors->uid, $editors->uid]);

    UserGroup::factory()->create(['name' => 'Writers']);

    $table = collect(get(cp_url('settings/cp-nav'))->assertOk()->inertiaProps('ui.nodes'))
        ->firstWhere('uid', 'cp-navigations')['props'];

    expect(array_column($table['createMenuItems'], 'label'))->toContain('Writers')->not->toContain('Editors', 'Contractors')
        ->and(array_map(fn (array $row) => [$row['name']['label'], $row['changes'], $row['_reorderable'] ?? true], $table['rows']))->toBe([
            ['Contractors', 'No changes', true],
            ['Editors', '1 hidden', true],
            ['Everyone else', 'No changes', false],
        ]);

    Edition::set(Edition::Team);

    get(cp_url('settings/cp-nav'))->assertRedirect(route('craft.cp.settings.cp-nav.default'));
});

it('deletes a user group’s navigation along with the group', function () {
    $group = UserGroups::getGroupByUid(UserGroup::factory()->create()->uid);
    app(CustomNavigations::class)->saveGroupNavigation($group, ['items' => ['nav-plugin-store' => ['hidden' => true]]]);

    UserGroups::deleteGroup($group);

    expect(app(ProjectConfig::class)->get(ProjectConfig::PATH_CP_NAV.'.groups'))->toBeEmpty();
});

it('shows added sections and links only to users who match their user condition, admins included', function () {
    $reviewers = UserGroup::factory()->create(['name' => 'Reviewers']);
    $inReviewers = ['class' => GroupConditionRule::class, 'operator' => 'in', 'values' => [$reviewers->uid]];
    $condition = fn (array $rules): array => ['class' => UserCondition::class, 'conditionRules' => $rules];

    actingAs(UserModel::factory()->admin()->createElement());
    $tree = get(cp_url('settings/cp-nav/default'))->viewData('page')['props']['tree'];

    $this->put(cp_url('settings/cp-nav/default'), ['nav' => [
        ...$tree,
        ['id' => 'custom-link00', 'type' => 'link', 'label' => 'Reviewers only', 'url' => 'entries', 'userCondition' => $condition([$inReviewers])],
        ['id' => 'custom-link01', 'type' => 'link', 'label' => 'Admins and reviewers', 'url' => 'entries', 'userCondition' => $condition([
            'operator' => 'or',
            'rules' => [['class' => AdminConditionRule::class, 'value' => true], $inReviewers],
        ])],
        ['id' => 'custom-link02', 'type' => 'link', 'label' => 'Everyone', 'url' => 'entries', 'userCondition' => $condition([])],
        ['id' => 'custom-sect00', 'type' => 'section', 'label' => 'Review', 'userCondition' => $condition([$inReviewers]), 'children' => [
            ['id' => 'custom-link03', 'type' => 'link', 'label' => 'Queue', 'url' => 'entries'],
        ]],
    ]])->assertSessionHasNoErrors();

    expect(app(CustomNavigations::class)->getDefault()['items']['custom-link02'])->not->toHaveKey('userCondition');

    $reviewer = UserModel::factory()->createElement();
    $reviewers->users()->attach($reviewer->id);
    $other = UserModel::factory()->createElement();
    UserPermissions::saveUserPermissions($reviewer->id, ['accessCp']);
    UserPermissions::saveUserPermissions($other->id, ['accessCp']);
    $added = ['Reviewers only', 'Admins and reviewers', 'Everyone', 'Review', 'Queue'];

    expect(array_values(array_intersect(topNavLabels(), $added)))->toBe(['Admins and reviewers', 'Everyone']);

    actingAs($reviewer);

    expect(array_values(array_intersect(topNavLabels(), $added)))->toBe(['Reviewers only', 'Admins and reviewers', 'Everyone', 'Review', 'Queue']);

    actingAs($other);

    expect(array_values(array_intersect(topNavLabels(), $added)))->toBe(['Everyone']);
});
