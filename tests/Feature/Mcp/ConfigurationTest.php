<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Mcp\Capabilities\Configuration;
use CraftCms\Cms\Mcp\Enums\ConfigurationOperation;
use CraftCms\Cms\Mcp\Enums\ConfigurationType;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Exception\ToolCallException;

beforeEach(function (): void {
    Edition::set(Edition::Pro);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    $this->call = fn (string $operation, array $arguments = []): TestResponse => McpRequest::send($this, 'tools/call', [
        'name' => "configuration.$operation",
        'arguments' => $arguments,
    ])->assertOk();
    $this->operate = fn (string $operation, string $type, array $arguments = []): array => ($this->call)($operation, ['type' => $type, ...$arguments])
        ->assertJsonPath('result.isError', false)
        ->json('result.structuredContent');
});

it('manages configuration records using the shared operations', function (string $type, array $attributes, array $changes, array $retained): void {
    $created = ($this->operate)('create', $type, ['attributes' => $attributes])['item'];
    $updated = ($this->operate)('update', $type, [
        'identifier' => isset($created['handle']) ? ['handle' => $created['handle']] : ['id' => $created['id']],
        'attributes' => $changes,
    ])['item'];
    $fetched = ($this->operate)('get', $type, ['identifier' => ['uid' => $created['uid']]])['item'];
    $listed = ($this->operate)('list', $type);
    $deleted = ($this->operate)('delete', $type, ['identifier' => ['uid' => $created['uid']]]);
    $after = ($this->operate)('list', $type);

    expect($created)->toMatchArray($retained)
        ->and($updated)->toMatchArray($changes)
        ->and($updated)->toMatchArray($retained)
        ->and($fetched)->toMatchArray(['uid' => $created['uid'], ...$changes, ...$retained])
        ->and(array_column($listed['items'], 'uid'))->toContain($created['uid'])
        ->and($deleted)->toBe(['type' => $type, 'deleted' => true])
        ->and(array_column($after['items'], 'uid'))->not->toContain($created['uid']);
})->with([
    'entry types' => ['entry-types', ['name' => 'Article', 'handle' => 'article', 'showSlugField' => false], ['handle' => 'story'], ['showSlugField' => false]],
    'user groups' => ['user-groups', ['name' => 'Editors', 'handle' => 'editors', 'description' => 'Editorial staff'], ['handle' => 'authors', 'description' => null], ['name' => 'Editors']],
    'image transforms' => ['image-transforms', ['name' => 'Thumbnail', 'handle' => 'thumbnail', 'width' => 200, 'height' => 100, 'quality' => 80, 'mode' => 'fit'], ['handle' => 'small', 'width' => null], ['height' => 100, 'quality' => 80, 'mode' => 'fit']],
    'site groups' => ['site-groups', ['name' => 'Archive'], ['name' => 'Legacy'], []],
]);

it('separates a section lookup handle from its new handle and preserves section settings', function (): void {
    $entryType = ($this->operate)('create', 'entry-types', ['attributes' => ['name' => 'Article', 'handle' => 'article']])['item'];
    $section = ($this->operate)('create', 'sections', ['attributes' => [
        'name' => 'News',
        'handle' => 'news',
        'type' => 'structure',
        'entryTypes' => [$entryType['id']],
        'siteSettings' => [['siteId' => Sites::getPrimarySite()->id, 'hasUrls' => false]],
        'enableVersioning' => false,
        'maxLevels' => 3,
    ]])['item'];
    $entry = Entry::factory()->createElement(['sectionId' => $section['id'], 'typeId' => $entryType['id']]);
    $updated = ($this->operate)('update', 'sections', [
        'identifier' => ['handle' => 'news'],
        'attributes' => ['handle' => 'articles', 'name' => 'Articles'],
    ])['item'];
    $fetched = ($this->operate)('get', 'sections', ['identifier' => ['id' => $section['id']]])['item'];
    ($this->operate)('delete', 'sections', ['identifier' => ['handle' => 'articles']]);

    expect($updated)->toMatchArray([
        'id' => $section['id'],
        'handle' => 'articles',
        'name' => 'Articles',
        'type' => 'structure',
        'enableVersioning' => false,
        'maxLevels' => 3,
    ])
        ->and($fetched)->toBe($updated)
        ->and(EntryElement::find()->id($entry->id)->one())->toBeNull()
        ->and(EntryElement::find()->id($entry->id)->trashed()->one())->not->toBeNull();
});

it('accepts route URI lookups and preserves omitted route attributes while clearing an explicit site UID', function (): void {
    $site = Sites::getPrimarySite();
    $route = ($this->operate)('create', 'routes', ['attributes' => [
        'uriParts' => ['blog/', ['slug', '[^/]+']],
        'template' => 'blog/_entry',
        'siteUid' => $site->uid,
    ]])['item'];
    $updated = ($this->operate)('update', 'routes', [
        'identifier' => ['uri' => 'blog/{slug}'],
        'attributes' => ['template' => 'news/_entry', 'siteUid' => null],
    ])['item'];
    $fetched = ($this->operate)('get', 'routes', ['identifier' => ['uid' => $route['uid']]])['item'];

    expect($updated)->toMatchArray([
        'uri' => 'blog/{slug}',
        'uriParts' => ['blog/', ['slug', '[^/]+']],
        'template' => 'news/_entry',
        'siteUid' => null,
    ])->and($fetched)->toBe($updated);

    ($this->operate)('delete', 'routes', ['identifier' => ['uri' => 'blog/{slug}']]);
    expect(($this->operate)('list', 'routes')['count'])->toBe(0);
});

it('passes site deletion options through to content transfer and protects populated site groups', function (): void {
    $primary = Sites::getPrimarySite();
    $site = ($this->operate)('create', 'sites', ['attributes' => [
        'name' => 'French', 'handle' => 'fr', 'language' => 'fr-FR', 'groupId' => $primary->groupId,
    ]])['item'];
    $entryType = ($this->operate)('create', 'entry-types', ['attributes' => ['name' => 'Article', 'handle' => 'article']])['item'];
    $section = ($this->operate)('create', 'sections', ['attributes' => [
        'name' => 'French news', 'handle' => 'frenchNews', 'entryTypes' => [$entryType['id']],
        'siteSettings' => [['siteId' => $site['id'], 'hasUrls' => false]],
    ]])['item'];
    $entry = new EntryElement(['sectionId' => $section['id'], 'typeId' => $entryType['id'], 'siteId' => $site['id'], 'title' => 'French news']);
    expect(Elements::saveElement($entry))->toBeTrue();
    ($this->operate)('update', 'sites', ['identifier' => ['handle' => 'fr'], 'attributes' => ['handle' => 'fr_ca']]);
    ($this->call)('delete', ['type' => 'site-groups', 'identifier' => ['id' => $primary->groupId]])
        ->assertJsonPath('result.isError', true);
    ($this->operate)('delete', 'sites', [
        'identifier' => ['handle' => 'fr_ca'],
        'options' => ['transferContentTo' => $primary->id],
    ]);

    expect(Sites::getSiteById($site['id']))->toBeNull()
        ->and(EntryElement::find()->id($entry->id)->siteId($primary->id)->one())->not->toBeNull();
});

it('returns usable operation schemas with the selected type’s lookup rules and validation constraints', function (): void {
    $schema = ($this->operate)('schema', 'routes', ['operation' => 'create'])['inputSchema'];
    $validator = app(SchemaValidator::class);

    expect($validator->validateAgainstJsonSchema([
        'type' => 'routes', 'attributes' => ['uriParts' => ['blog/', ['slug', '[^/]+']], 'template' => 'blog/_entry'],
    ], $schema))->toBe([])
        ->and($validator->validateAgainstJsonSchema([
            'type' => 'routes', 'attributes' => ['uriParts' => [['slug']], 'template' => 'blog/_entry'],
        ], $schema))->not->toBe([])
        ->and($validator->validateAgainstJsonSchema([
            'type' => 'routes', 'attributes' => ['uriParts' => ['blog'], 'template' => 'blog/_entry', 'siteUid' => 'invalid'],
        ], $schema))->not->toBe([]);

    $lookup = ($this->operate)('schema', 'site-groups', ['operation' => 'get'])['inputSchema'];
    expect($validator->validateAgainstJsonSchema(['type' => 'site-groups', 'identifier' => ['id' => 1]], $lookup))->toBe([])
        ->and($validator->validateAgainstJsonSchema(['type' => 'site-groups', 'identifier' => ['handle' => 'group']], $lookup))->not->toBe([])
        ->and($validator->validateAgainstJsonSchema(['type' => 'site-groups', 'identifier' => ['id' => 1, 'uid' => '00000000-0000-4000-8000-000000000000']], $lookup))->not->toBe([]);
});

it('rejects inputs outside the selected operation schema before modifying configuration', function (string $operation, array $arguments, string $message): void {
    ($this->call)($operation, $arguments)
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', fn (string $text): bool => str_contains($text, $message));

    expect(($this->operate)('list', 'routes')['count'])->toBe(0)
        ->and(($this->operate)('list', 'fields')['count'])->toBe(0);
})->with([
    'missing create value' => ['create', ['type' => 'routes', 'attributes' => ['template' => 'blog/_entry']], '/attributes'],
    'invalid URI tuple' => ['create', ['type' => 'routes', 'attributes' => ['uriParts' => [['slug']], 'template' => 'blog/_entry']], '/attributes/uriParts'],
    'unsupported lookup key' => ['get', ['type' => 'routes', 'identifier' => ['id' => 1]], '/identifier'],
    'invalid UID' => ['get', ['type' => 'fields', 'identifier' => ['uid' => 'invalid']], '/identifier/uid'],
    'ambiguous lookup' => ['delete', ['type' => 'fields', 'identifier' => ['id' => 1, 'handle' => 'summary']], '/identifier'],
    'changing field class' => ['update', ['type' => 'fields', 'identifier' => ['id' => 1], 'attributes' => ['type' => 'invalid']], '/attributes'],
    'unknown deletion option' => ['delete', ['type' => 'routes', 'identifier' => ['uri' => 'blog'], 'options' => ['hardDelete' => true]], '/options'],
    'invalid transform dimensions' => ['create', ['type' => 'image-transforms', 'attributes' => ['name' => 'Small', 'handle' => 'small', 'width' => 0]], '/attributes/width'],
]);

it('keeps read-only admin discovery usable while refusing configuration writes', function (): void {
    Cms::config()->allowAdminChanges(false);
    ($this->operate)('list', 'sites');
    $types = ($this->call)('schema')->assertJsonPath('result.isError', false)->json('result.structuredContent.types');
    expect($types)->not->toBeEmpty();

    foreach ($types as $type) {
        expect($type['operations'])->toBe(['list', 'get']);
    }

    McpRequest::send($this, 'tools/call', ['name' => 'configuration.create', 'arguments' => [
        'type' => 'site-groups', 'attributes' => ['name' => 'New group'],
    ]])->assertBadRequest()->assertJsonPath('error.message', 'Tool not found: "configuration.create".');
    ($this->call)('schema', ['type' => 'sites', 'operation' => 'delete'])->assertJsonPath('result.isError', true);

    expect(fn () => app(Configuration::class)->schema(ConfigurationType::Sites, ConfigurationOperation::Delete))
        ->toThrow(ToolCallException::class, 'Admin changes are disabled.');
});

it('does not expose configuration to users without admin access', function (): void {
    $user = User::factory()->withPermissions(['accessCp', 'useCraftMcp'])->create(['admin' => false]);
    Passport::actingAs($user, ['mcp:use'], 'craft-mcp');

    McpRequest::send($this, 'tools/call', ['name' => 'configuration.list', 'arguments' => ['type' => 'sites']])
        ->assertBadRequest()->assertJsonPath('error.message', 'Tool not found: "configuration.list".');
});
