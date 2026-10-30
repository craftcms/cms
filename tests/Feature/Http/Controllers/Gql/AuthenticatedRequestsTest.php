<?php

declare(strict_types=1);

use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\UserPermissions;
use Illuminate\Contracts\Auth\Factory;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\ClientRepository;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    openssl_pkey_export($key, $privateKey);
    config()->set('passport.private_key', $privateKey);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);

    gqlDisablePublicToken();

    $entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($entryType)->create(['handle' => 'news']);
    EntryTypes::refreshEntryTypes();
    $this->editor = UserModel::factory()
        ->withPermissions([
            'accessCp',
            "viewEntries:{$this->section->uid}",
            "saveEntries:{$this->section->uid}",
            "deleteEntries:{$this->section->uid}",
        ])
        ->createElement(['admin' => false]);

    $this->own = EntryModel::factory()->forSection($this->section)->forEntryType($entryType)->createElement();
    $this->own->setAuthorIds([$this->editor->id]);
    Elements::saveElement($this->own);
    $this->peer = EntryModel::factory()->forSection($this->section)->forEntryType($entryType)->createElement();
});

function gqlUserToken(User $user, array $scopes = ['graphql:read']): string
{
    return app(PersonalAccessTokens::class)->create($user, 'Test', $scopes, new DateInterval('P7D'));
}

function gqlAsUser(string $token, string $query): TestResponse
{
    app(Factory::class)->forgetGuards();

    return graphQL($query, ['Authorization' => "Bearer $token"]);
}

it('only returns the entries the token’s user can view', function (): void {
    gqlAsUser(gqlUserToken($this->editor), '{ entries(section: "news") { id } }')
        ->assertOk()
        ->assertJsonPath('data.entries', [['id' => (string) $this->own->id]]);
});

it('varies cached results by the user’s permissions', function (): void {
    Cms::config()->enableGraphqlCaching = true;
    actingAs(UserModel::findOrFail($this->editor->id));
    $gql = app(Gql::class);
    $query = '{ entries(section: "news") { id } }';
    $ids = fn (): array => collect($gql->executeQuery($gql->getSchemaForUser($this->editor, false), $query)['data']['entries'])->pluck('id')->all();

    expect($ids())->toBe([(string) $this->own->id]);

    app(UserPermissions::class)->saveUserPermissions($this->editor->id, [
        'accessCp',
        "viewEntries:{$this->section->uid}",
        "viewPeerEntries:{$this->section->uid}",
    ]);

    expect($ids())->toEqualCanonicalizing([(string) $this->own->id, (string) $this->peer->id]);
});

it('only lets write tokens delete entries the user may delete', function (): void {
    $writeToken = gqlUserToken($this->editor, ['graphql:read', 'graphql:write']);

    gqlAsUser($writeToken, "mutation { deleteEntry(id: {$this->peer->id}) }")
        ->assertJsonPath('errors.0.message', 'Unable to perform the action.');

    gqlAsUser(gqlUserToken($this->editor), "mutation { deleteEntry(id: {$this->own->id}) }")
        ->assertJsonPath('data', null);

    expect(Entry::find()->id([$this->own->id, $this->peer->id])->status(null)->count())->toBe(2);

    gqlAsUser($writeToken, "mutation { deleteEntry(id: {$this->own->id}) }")
        ->assertJsonPath('data.deleteEntry', true);

    expect(Entry::find()->id($this->own->id)->status(null)->exists())->toBeFalse()
        ->and(Entry::find()->id($this->peer->id)->status(null)->exists())->toBeTrue();
});

it('returns the token’s user as currentUser', function (): void {
    gqlAsUser(gqlUserToken($this->editor), '{ currentUser { id } }')
        ->assertOk()
        ->assertJsonPath('data.currentUser.id', (string) $this->editor->id);
});

it('rejects invalid access tokens and tokens without a GraphQL scope', function (Closure $token): void {
    gqlAsUser($token($this->editor), '{ __typename }')
        ->assertUnauthorized()
        ->assertHeader('WWW-Authenticate', 'Bearer error="invalid_token", scope="graphql:read graphql:write"');
})->with([
    'malformed' => [fn (User $user): string => 'not.a.jwt'],
    'no GraphQL scope' => [function (User $user): string {
        app(ClientRepository::class)->createPersonalAccessGrantClient('Personal Access Client', 'craft');

        return UserModel::findOrFail($user->id)->createToken('MCP', ['mcp:read'])->accessToken;
    }],
]);
