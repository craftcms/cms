<?php

declare(strict_types=1);

use CraftCms\Cms\Cms;
use CraftCms\Cms\Cp\CustomNavigations;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Conditions\EntryCondition;
use CraftCms\Cms\Entry\Elements\Entry as EntryElement;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Field\Entries as EntriesField;
use CraftCms\Cms\Field\Models\Field as FieldModel;
use CraftCms\Cms\FieldLayout\LayoutElements\CustomField;
use CraftCms\Cms\Mcp\Capabilities\Configuration;
use CraftCms\Cms\Mcp\Enums\ConfigurationOperation;
use CraftCms\Cms\Mcp\Enums\ConfigurationType;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\Elements;
use CraftCms\Cms\Support\Facades\Fields as FieldsService;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Conditions\AdminConditionRule;
use CraftCms\Cms\User\Conditions\UserCondition;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\Models\UserGroup;
use CraftCms\Cms\Workflow\UserReview\UserReviewStage;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Exception\ToolCallException;

beforeEach(function (): void {
    Edition::set(Edition::Pro);
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:admin'], 'craft-mcp');
    $this->call = fn (string $operation, array $arguments = []): TestResponse => McpRequest::send($this, 'tools/call', [
        'name' => "configuration_$operation",
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
    'asset transformers' => ['asset-transformers', ['name' => 'Local', 'handle' => 'local', 'driver' => 'craft'], ['handle' => 'localImages'], ['name' => 'Local', 'driver' => 'craft']],
]);

it('manages workflows, keeping stages that aren’t replaced', function (): void {
    $group = UserGroup::factory()->create();
    $stage = ['name' => 'Editor review', 'type' => UserReviewStage::class, 'settings' => ['userGroups' => [$group->uid], 'approvalsRequired' => 1, 'approvalMode' => 'total']];
    $created = ($this->operate)('create', 'workflows', ['attributes' => ['name' => 'Review', 'stages' => [$stage]]])['item'];
    $updated = ($this->operate)('update', 'workflows', ['identifier' => ['id' => $created['id']], 'attributes' => ['name' => 'Legal review']])['item'];

    expect($created['stages'][0])->toMatchArray($stage)
        ->and($created['stages'][0]['uid'])->toBeString()
        ->and($updated)->toMatchArray(['id' => $created['id'], 'name' => 'Legal review', 'stages' => $created['stages']]);

    ($this->call)('update', ['type' => 'workflows', 'identifier' => ['id' => $created['id']], 'attributes' => ['stages' => [[...$stage, 'settings' => []]]]])
        ->assertJsonPath('result.isError', true);

    $section = Section::factory()->create(['workflowId' => $created['id']]);

    ($this->call)('delete', ['type' => 'workflows', 'identifier' => ['id' => $created['id']]])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'This workflow cannot be deleted while it is assigned to a section.');

    $section->update(['workflowId' => null]);

    expect(($this->operate)('delete', 'workflows', ['identifier' => ['uid' => $created['uid']]]))->toBe(['type' => 'workflows', 'deleted' => true]);
});

it('explains that workflows require Craft Pro', function (): void {
    Edition::set(Edition::Team);

    $types = collect(($this->call)('schema')->json('result.structuredContent.types'))->keyBy('type');

    expect($types['asset-transformers']['operations'])->not->toBeEmpty()
        ->and($types['workflows'])->toBe(['type' => 'workflows', 'operations' => [], 'requiredEdition' => 'Craft Pro']);

    ($this->call)('list', ['type' => 'workflows'])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'The workflows configuration type requires Craft Pro. This site runs Craft Team.');
});

it('manages CP navigations as the trees the navigation builder edits', function (): void {
    $editors = UserGroup::factory()->create(['name' => 'Editors', 'handle' => 'editors']);
    UserGroup::factory()->create(['name' => 'Authors', 'handle' => 'authors']);
    $navigations = app(CustomNavigations::class);

    $tree = ($this->operate)('get', 'cp-navigations', ['identifier' => ['userGroup' => 'default']])['item']['tree'];
    $first = $tree[0]['id'];
    $tree[0]['hidden'] = true;

    expect(($this->operate)('update', 'cp-navigations', ['identifier' => ['userGroup' => 'default'], 'attributes' => ['tree' => $tree]])['item']['changes'])->toBe('1 hidden')
        ->and($navigations->getDefault()['items'][$first])->toBe(['hidden' => true]);

    ($this->operate)('create', 'cp-navigations', ['attributes' => ['userGroup' => 'authors']]);
    $groupTree = ($this->operate)('get', 'cp-navigations', ['identifier' => ['userGroup' => 'authors']])['item']['tree'];
    $groupTree[0]['label'] = 'Home';
    $created = ($this->operate)('create', 'cp-navigations', ['attributes' => ['userGroup' => $editors->uid, 'tree' => $groupTree, 'position' => 1]])['item'];

    expect($created)->toMatchArray(['userGroup' => 'editors', 'position' => 1, 'changes' => '1 edited'])
        ->and($created['tree'][0]['label'])->toBe('Home')
        ->and(array_column(($this->operate)('list', 'cp-navigations')['items'], 'userGroup'))->toBe(['editors', 'authors', 'default']);

    ($this->call)('create', ['type' => 'cp-navigations', 'attributes' => ['userGroup' => 'editors']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'The “Editors” user group already has a navigation. Update it instead.');

    expect(($this->operate)('update', 'cp-navigations', ['identifier' => ['userGroup' => 'editors'], 'attributes' => ['position' => 2]])['item']['position'])->toBe(2)
        ->and(($this->operate)('delete', 'cp-navigations', ['identifier' => ['userGroup' => 'editors']]))->toBe(['type' => 'cp-navigations', 'deleted' => true])
        ->and($navigations->getGroupNavigation($editors->uid))->toBeNull()
        ->and(($this->operate)('delete', 'cp-navigations', ['identifier' => ['userGroup' => 'default']]))->toBe(['type' => 'cp-navigations', 'deleted' => true])
        ->and($navigations->getDefault())->toBe([]);
});

it('builds user group CP navigations on the default navigation', function (): void {
    UserGroup::factory()->create(['handle' => 'editors']);
    $default = ($this->operate)('get', 'cp-navigations', ['identifier' => ['userGroup' => 'default']])['item']['tree'];
    $default[] = ['id' => 'custom-docs', 'kind' => 'item', 'source' => 'custom', 'type' => 'link', 'label' => 'Docs', 'url' => 'https://craftcms.com/docs'];
    ($this->operate)('update', 'cp-navigations', ['identifier' => ['userGroup' => 'default'], 'attributes' => ['tree' => $default]]);

    $created = ($this->operate)('create', 'cp-navigations', ['attributes' => ['userGroup' => 'editors']])['item'];
    $tree = $created['tree'];
    $docs = array_search('custom-docs', array_column($tree, 'id'), true);
    $tree[$docs]['hidden'] = true;
    $hidden = ($this->operate)('update', 'cp-navigations', ['identifier' => ['userGroup' => 'editors'], 'attributes' => ['tree' => $tree]])['item'];

    expect($created['changes'])->toBe('No changes')
        ->and($docs)->not->toBeFalse()
        ->and($hidden['changes'])->toBe('1 hidden')
        ->and(app(CustomNavigations::class)->getGroupNavigation(UserGroup::query()->where('handle', 'editors')->firstOrFail()->uid)['nav'])
        ->toBe(['items' => ['custom-docs' => ['hidden' => true]]]);

    $default[0]['label'] = 'Home';
    ($this->operate)('update', 'cp-navigations', ['identifier' => ['userGroup' => 'default'], 'attributes' => ['tree' => $default]]);

    expect(($this->operate)('get', 'cp-navigations', ['identifier' => ['userGroup' => 'editors']])['item']['tree'][0]['label'])->toBe('Home');
});

it('keeps user group navigations to Craft Pro and CP navigations to Craft Team', function (): void {
    UserGroup::factory()->create(['handle' => 'editors']);
    Edition::set(Edition::Team);

    expect(($this->operate)('get', 'cp-navigations', ['identifier' => ['userGroup' => 'default']])['item']['tree'])->not->toBeEmpty()
        ->and(array_column(($this->operate)('list', 'cp-navigations')['items'], 'userGroup'))->toBe(['default']);

    ($this->call)('create', ['type' => 'cp-navigations', 'attributes' => ['userGroup' => 'editors']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'User group navigations require Craft Pro. This site runs Craft Team.');

    Edition::set(Edition::Solo);

    expect(collect(($this->call)('schema')->json('result.structuredContent.types'))->keyBy('type')['cp-navigations'])
        ->toBe(['type' => 'cp-navigations', 'operations' => [], 'requiredEdition' => 'Craft Team']);
});

it('refuses CP navigation links and ids the navigation builder would refuse', function (): void {
    $tree = ($this->operate)('get', 'cp-navigations', ['identifier' => ['userGroup' => 'default']])['item']['tree'];
    $tree[] = ['id' => 'custom-test', 'kind' => 'item', 'source' => 'custom', 'type' => 'link', 'label' => 'Bad', 'url' => 'javascript:alert(1)'];

    ($this->call)('update', ['type' => 'cp-navigations', 'identifier' => ['userGroup' => 'default'], 'attributes' => ['tree' => $tree]])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'The nav.'.(count($tree) - 1).'.url field is invalid.');

    $tree = array_slice($tree, 0, -1);
    $refuse = fn (array $node, string $error) => ($this->call)('update', ['type' => 'cp-navigations', 'identifier' => ['userGroup' => 'default'], 'attributes' => ['tree' => [...$tree, $node]]])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', fn (string $text): bool => str_starts_with($text, $error));

    $refuse(['id' => 'custom-news-page', 'kind' => 'item', 'source' => 'custom', 'type' => 'link', 'label' => 'News', 'url' => 'content/entries'], 'The navigation can’t have `custom-news-page`.');
    $refuse(['id' => 'docs', 'kind' => 'item', 'type' => 'link', 'label' => 'Docs', 'url' => 'https://craftcms.com/docs'], 'The navigation can’t have `docs`.');
    $refuse(['id' => 'custom-docs', 'kind' => 'item', 'source' => 'custom', 'type' => 'link', 'url' => 'https://craftcms.com/docs'], 'The navigation can’t have `custom-docs`.');
    $refuse(['id' => 'custom-docs', 'kind' => 'item', 'source' => 'custom', 'type' => 'link', 'label' => 'Docs', 'url' => 'https://craftcms.com/docs', 'userCondition' => ['class' => EntryCondition::class]], 'The condition isn’t valid.');

    expect(app(CustomNavigations::class)->getDefault())->toBe([]);
});

it('needs the whole CP navigation tree, so nodes left out keep their changes', function (): void {
    UserGroup::factory()->create(['handle' => 'editors']);
    $tree = ($this->operate)('get', 'cp-navigations', ['identifier' => ['userGroup' => 'default']])['item']['tree'];
    $tree[0]['label'] = 'Home';
    $tree[1]['hidden'] = true;
    $tree[] = ['id' => 'custom-docs', 'kind' => 'item', 'source' => 'custom', 'type' => 'link', 'label' => 'Docs', 'url' => 'https://craftcms.com/docs', 'userCondition' => [
        'class' => UserCondition::class,
        'conditionRules' => [['class' => AdminConditionRule::class, 'value' => true]],
    ]];
    ($this->operate)('update', 'cp-navigations', ['identifier' => ['userGroup' => 'default'], 'attributes' => ['tree' => $tree]]);
    ($this->operate)('create', 'cp-navigations', ['attributes' => ['userGroup' => 'editors']]);
    $saved = app(CustomNavigations::class)->getDefault();

    foreach (['default', 'editors'] as $userGroup) {
        ($this->call)('update', ['type' => 'cp-navigations', 'identifier' => ['userGroup' => $userGroup], 'attributes' => ['tree' => [[...$tree[0], 'label' => 'Start']]]])
            ->assertJsonPath('result.isError', true)
            ->assertJsonPath('result.content.0.text', fn (string $text): bool => str_starts_with($text, 'The tree leaves out `') && str_contains($text, ' more. Pass the whole tree'));
    }

    expect($saved['items']['custom-docs']['userCondition']['conditionRules']['rules'][0])->toMatchArray(['class' => AdminConditionRule::class, 'value' => true])
        ->and(app(CustomNavigations::class)->getDefault())->toBe($saved)
        ->and(app(CustomNavigations::class)->getGroupNavigation(UserGroup::query()->where('handle', 'editors')->firstOrFail()->uid)['nav'])->toBe([]);

    // A custom node the navigation adds itself is removed by leaving it out.
    $removed = ($this->operate)('update', 'cp-navigations', ['identifier' => ['userGroup' => 'default'], 'attributes' => ['tree' => array_slice($tree, 0, -1)]])['item'];

    expect(array_column($removed['tree'], 'id'))->not->toContain('custom-docs');
});

it('refuses asset transformer changes Craft doesn’t allow', function (): void {
    ($this->call)('create', ['type' => 'asset-transformers', 'attributes' => ['name' => 'Remote', 'handle' => 'remote', 'driver' => 'nope']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Unknown asset transform driver [nope]. Available drivers: craft.');

    ($this->call)('delete', ['type' => 'asset-transformers', 'identifier' => ['handle' => 'craft']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'The Craft asset transformer cannot be deleted.');
});

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

    McpRequest::send($this, 'tools/call', ['name' => 'configuration_create', 'arguments' => [
        'type' => 'site-groups', 'attributes' => ['name' => 'New group'],
    ]])->assertBadRequest()->assertJsonPath('error.message', 'Tool not found: "configuration_create".');
    ($this->call)('schema', ['type' => 'sites', 'operation' => 'delete'])->assertJsonPath('result.isError', true);

    expect(fn () => app(Configuration::class)->schema(ConfigurationType::Sites, ConfigurationOperation::Delete))
        ->toThrow(ToolCallException::class, 'Admin changes are disabled.');
});

it('does not expose configuration to users without admin access', function (): void {
    $user = User::factory()->withPermissions(['accessCp'])->create(['admin' => false]);
    Passport::actingAs($user, ['mcp:admin'], 'craft-mcp');

    McpRequest::send($this, 'tools/call', ['name' => 'configuration_list', 'arguments' => ['type' => 'sites']])
        ->assertBadRequest()->assertJsonPath('error.message', 'Tool not found: "configuration_list".');
});

it('rejects nonexistent section sources and layout fields before saving', function (): void {
    $missing = '00000000-0000-4000-8000-000000000000';
    $entryType = ($this->operate)('create', 'entry-types', ['attributes' => ['name' => 'Article', 'handle' => 'article']])['item'];
    $section = ($this->operate)('create', 'sections', ['attributes' => [
        'name' => 'News',
        'handle' => 'news',
        'type' => 'channel',
        'entryTypes' => [$entryType['id']],
        'siteSettings' => [['siteId' => Sites::getPrimarySite()->id, 'hasUrls' => false]],
    ]])['item'];
    $rejects = fn (string $operation, array $arguments, string $message) => ($this->call)($operation, $arguments)
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', fn (string $text): bool => str_contains($text, $message));
    $entries = fn (array $sources, string $handle = 'related'): array => ['type' => 'fields', 'attributes' => [
        'type' => EntriesField::class,
        'name' => 'Related',
        'handle' => $handle,
        'settings' => ['sources' => $sources],
    ]];

    $rejects('create', $entries(['*', "section:$missing"]), "Source “section:{$missing}” doesn’t exist.");
    expect(FieldModel::query()->where('handle', 'related')->exists())->toBeFalse();

    $field = ($this->operate)('create', 'fields', $entries(["section:{$section['uid']}"]))['item'];
    ($this->operate)('create', 'fields', $entries(['*'], 'anything'));

    $rejects('update', ['type' => 'fields', 'identifier' => ['id' => $field['id']], 'attributes' => [
        'settings' => ['sources' => ["section:{$section['uid']}", 'section:news']],
    ]], 'Source “section:news” doesn’t exist.');
    FieldsService::refreshFields();
    expect(FieldsService::getFieldById($field['id'])->sources)->toBe(["section:{$section['uid']}"]);

    $layout = fn (string $fieldUid): array => ['tabs' => [['name' => 'Content', 'elements' => [
        ['type' => CustomField::class, 'fieldUid' => $field['uid']],
        ['type' => CustomField::class, 'fieldUid' => $fieldUid],
    ]]]];
    $rejects('update', ['type' => 'entry-types', 'identifier' => ['uid' => $entryType['uid']], 'attributes' => [
        'fieldLayout' => $layout($missing),
    ]], "Field “{$missing}” doesn’t exist.");
    expect(DB::table(Table::FIELDLAYOUTS)->where('config', 'like', "%$missing%")->exists())->toBeFalse();
});
