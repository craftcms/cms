<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Element as BaseElement;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\Elements\BaseElementAdapter;
use CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');

    $this->call = fn (string $tool, array $arguments): TestResponse => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => $arguments,
    ])->assertOk();
});

it('describes each element type through elements.schema and the element type resources', function (): void {
    $types = McpRequest::send($this, 'resources/read', ['uri' => 'craft://element-types'])->json('result.contents.0.text');
    $assets = McpRequest::send($this, 'resources/read', ['uri' => 'craft://element-types/assets'])->json('result.contents.0.text');

    expect(json_decode($types, true)['types'])->toBe([
        ['type' => 'entries', 'name' => 'Entries', 'elementType' => Entry::class, 'schema' => 'craft://element-types/entries'],
        ['type' => 'assets', 'name' => 'Assets', 'elementType' => Asset::class, 'schema' => 'craft://element-types/assets'],
        ['type' => 'users', 'name' => 'Users', 'elementType' => UserElement::class, 'schema' => 'craft://element-types/users'],
        ['type' => 'addresses', 'name' => 'Addresses', 'elementType' => Address::class, 'schema' => 'craft://element-types/addresses'],
    ])
        ->and(json_decode($assets, true))->not->toHaveKey('createAttributes')
        ->and(json_decode($assets, true)['notes'][0])->toContain('assets.create');

    ($this->call)('elements.schema', ['type' => 'entries'])
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.createAttributes.required', ['sectionId'])
        ->assertJsonPath('result.structuredContent.criteria.properties.sectionId.description', 'Section ID or IDs.')
        ->assertJsonPath('result.structuredContent.duplicateModes', ['unpublished', 'canonical']);
});

it('rejects element tool inputs that the type does not accept', function (string $tool, array $arguments, string $message): void {
    ($this->call)($tool, $arguments)
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', fn (string $text): bool => str_contains($text, $message));
})->with([
    'unknown type' => ['elements.get', ['type' => 'widgets', 'id' => 1], 'Unsupported element type [widgets]. Supported types: entries, assets, users, addresses.'],
    'criteria of the wrong type' => ['elements.list', ['type' => 'entries', 'criteria' => ['sectionId' => 'news']], 'Invalid criteria for entries: /sectionId:'],
    'missing create attribute' => ['elements.create', ['type' => 'entries', 'attributes' => ['title' => 'No section']], 'Invalid attributes for entries:'],
    'undeclared update attribute' => ['elements.update', ['type' => 'addresses', 'id' => 1, 'attributes' => ['id' => 2]], 'Invalid attributes for addresses: Additional object properties are not allowed: ["id"]'],
    'context for a type with one layout' => ['elements.field-schema', ['type' => 'users', 'context' => ['sectionId' => 1]], 'Users do not accept a field-schema context.'],
    'asset creation without a file' => ['elements.create', ['type' => 'assets', 'attributes' => ['title' => 'Logo']], 'Create assets with assets.create, which accepts the file to upload.'],
]);

it('manages element types that plugins register with an adapter', function (): void {
    app(ElementTypes::class)->register(TestMcpPluginElement::class);
    app(ElementAdapterRegistry::class)->register(TestMcpPluginElementAdapter::class);

    $created = ($this->call)('elements.create', ['type' => 'widgets', 'attributes' => ['title' => 'Sprocket']])
        ->assertJsonPath('result.isError', false)
        ->json('result.structuredContent.element');

    ($this->call)('elements.update', ['type' => 'widgets', 'id' => $created['id'], 'attributes' => ['title' => 'Cog']])
        ->assertJsonPath('result.isError', false);
    ($this->call)('elements.list', ['type' => 'widgets'])
        ->assertJsonPath('result.structuredContent.count', 1)
        ->assertJsonPath('result.structuredContent.elements.0.id', $created['id']);
    ($this->call)('elements.get', ['type' => 'widgets', 'id' => $created['id']])
        ->assertJsonPath('result.structuredContent.element.title', 'Cog');
    ($this->call)('elements.update', ['type' => 'widgets', 'id' => $created['id'], 'attributes' => ['slug' => 'cog']])
        ->assertJsonPath('result.isError', true)
        ->assertJsonPath('result.content.0.text', 'Invalid attributes for widgets: Additional object properties are not allowed: ["slug"]. Call elements.schema for the accepted attributes.');
    ($this->call)('elements.delete', ['type' => 'widgets', 'id' => $created['id']])
        ->assertJsonPath('result.structuredContent.deleted', true);

    expect(TestMcpPluginElement::find()->id($created['id'])->exists())->toBeFalse();
});

class TestMcpPluginElement extends BaseElement
{
    #[Override]
    public static function displayName(): string
    {
        return 'Widget';
    }

    #[Override]
    public static function pluralDisplayName(): string
    {
        return 'Widgets';
    }

    #[Override]
    public static function hasTitles(): bool
    {
        return true;
    }

    #[Override]
    public function canView(UserElement $user): bool
    {
        return true;
    }

    #[Override]
    public function canSave(UserElement $user): bool
    {
        return true;
    }

    #[Override]
    public function canDelete(UserElement $user): bool
    {
        return true;
    }
}

class TestMcpPluginElementAdapter extends BaseElementAdapter
{
    public static function handle(): string
    {
        return 'widgets';
    }

    public static function elementType(): string
    {
        return TestMcpPluginElement::class;
    }

    protected function createAttributesSchema(): array
    {
        return $this->updateAttributesSchema();
    }

    protected function updateAttributesSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => ['title' => ['type' => 'string']],
            'additionalProperties' => false,
        ];
    }
}
