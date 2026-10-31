<?php

declare(strict_types=1);

use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Gql\Data\GqlSchema;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Gql\Interfaces\Elements\Entry as EntryInterface;
use CraftCms\Cms\Gql\Queries\Release as ReleaseQuery;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    app(Gql::class)->flushCaches();
    Cms::config()->enableGraphqlCaching = false;
    Edition::set(Edition::Pro);
    $this->actor = User::findOne();
    actingAs($this->actor);
});

it('nests the releases component under drafts', function () {
    $components = app(Gql::class)->getAllSchemaComponents();
    $elementComponents = array_merge(...array_values($components['queries']));

    expect($elementComponents['elements.drafts:read']['nested'] ?? [])->toHaveKey('elements.releases:read');
});

it('omits the releases component on editions without releases', function () {
    Edition::set(Edition::Team);

    $components = app(Gql::class)->getAllSchemaComponents();
    $elementComponents = array_merge(...array_values($components['queries']));

    expect($elementComponents['elements.drafts:read'])->not->toHaveKey('nested');
});

it('lets authenticated requests query releases for control panel users', function (array $permissions, bool $expected) {
    $user = UserModel::factory()->withPermissions($permissions)->createElement(['admin' => false]);

    expect(in_array('elements.releases:read', app(Gql::class)->getSchemaForUser($user, false)->scope, true))->toBe($expected);
})->with([
    'control panel access' => [['accessCp'], true],
    'no control panel access' => [[], false],
]);

it('requires both the releases and drafts components', function (array $scope, bool $expected) {
    $gql = app(Gql::class);
    $gql->setActiveSchema(new GqlSchema(['scope' => $scope]));

    expect(array_keys(ReleaseQuery::getQueries()))->toBe($expected ? ['releases', 'release'] : [])
        ->and(array_key_exists('release', EntryInterface::getFieldDefinitions()))->toBe($expected);
})->with([
    'releases and drafts' => [['elements.drafts:read', 'elements.releases:read'], true],
    'releases only' => [['elements.releases:read'], false],
    'drafts only' => [['elements.drafts:read'], false],
]);

it('queries releases and the release a draft is attached to', function () {
    $releases = app(Releases::class);
    $canonical = EntryModel::factory()->createElement(['title' => 'Live title']);
    $draft = app(Drafts::class)->createDraft($canonical, $this->actor->id);
    expect(Elements::saveElement($draft))->toBeTrue();

    $launch = $releases->create('Launch', null, $this->actor, 'Spring launch');
    $releases->attach($launch, $draft, $this->actor);
    $releases->create('Other', null, $this->actor);
    gqlActivateFullAccessSchema();

    graphQL(<<<GQL
        {
          releases { name }
          release(uid: "{$launch->uid}") { id name description status }
          missing: release(status: "published") { id }
          entries(releaseId: {$launch->id}) { release { id name } }
        }
        GQL)
        ->assertOk()
        ->assertJsonMissingPath('errors')
        ->assertJsonPath('data.releases', [['name' => 'Launch'], ['name' => 'Other']])
        ->assertJsonPath('data.release', [
            'id' => $launch->id,
            'name' => 'Launch',
            'description' => 'Spring launch',
            'status' => 'draft',
        ])
        ->assertJsonPath('data.missing', null)
        ->assertJsonPath('data.entries', [['release' => ['id' => $launch->id, 'name' => 'Launch']]]);
});

it('queries whether a volume’s assets have URLs', function () {
    $asset = Asset::factory()->createElement();
    gqlActivateFullAccessSchema();

    graphQL("{ asset(id: {$asset->id}) { volume { hasUrls } } }")
        ->assertOk()
        ->assertJsonPath('data.asset.volume.hasUrls', $asset->volume->hasUrls);
});
