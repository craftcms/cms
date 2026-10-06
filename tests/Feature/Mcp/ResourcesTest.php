<?php

declare(strict_types=1);

use CraftCms\Cms\Address\Models\Address;
use CraftCms\Cms\Asset\Models\Asset;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Entry\Models\Entry;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Models\Field;
use CraftCms\Cms\Field\PlainText;
use CraftCms\Cms\FieldLayout\Models\FieldLayout;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use CraftCms\Cms\User\UserPermissions;
use Laravel\Passport\Passport;

beforeEach(function (): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
});

it('returns readable resource links alongside element list data and checks access again on read', function (
    Closure $createElement,
    string $plural,
    string $singular,
    array $criteria = [],
): void {
    Edition::set(Edition::Pro);
    $actor = User::query()->firstOrFail();
    Passport::actingAs($actor, ['mcp:use'], 'craft-mcp');
    $element = $createElement();
    $criteria = ['id' => $element->id, 'status' => null, 'drafts' => null, ...$criteria];
    $uri = $plural === 'users'
        ? "craft://users/$element->id"
        : "craft://$plural/$element->id/sites/$element->siteId";

    $listed = McpRequest::send($this, 'tools/call', [
        'name' => "$plural.list",
        'arguments' => ['criteria' => $criteria],
    ])
        ->assertOk()
        ->assertJsonPath('result.isError', false)
        ->assertJsonPath('result.structuredContent.count', 1)
        ->assertJsonPath("result.structuredContent.$plural.0.id", $element->id)
        ->assertJsonPath('result.content.1.type', 'resource_link')
        ->assertJsonPath('result.content.1.uri', $uri)
        ->assertJsonPath('result.content.1.mimeType', 'application/json')
        ->json('result');

    expect(json_decode($listed['content'][0]['text'], true, flags: JSON_THROW_ON_ERROR))
        ->toBe($listed['structuredContent']);

    $resource = McpRequest::send($this, 'resources/read', ['uri' => $uri])
        ->assertOk()
        ->assertJsonPath('result.contents.0.uri', $uri)
        ->json('result.contents.0.text');

    expect(json_decode($resource, true, flags: JSON_THROW_ON_ERROR)[$singular]['id'])->toBe($element->id);

    $actor->admin = false;
    $actor->save();
    app(UserPermissions::class)->saveUserPermissions($actor->id, ['accessCp', 'useCraftMcp']);
    Passport::actingAs($actor, ['mcp:use'], 'craft-mcp');

    McpRequest::send($this, 'resources/read', ['uri' => $uri])
        ->assertBadRequest()
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonMissingPath('result.contents');

    if ($plural !== 'users') {
        McpRequest::send($this, 'tools/call', [
            'name' => "$plural.list",
            'arguments' => ['criteria' => $criteria],
        ])
            ->assertOk()
            ->assertJsonPath('result.structuredContent.count', 0)
            ->assertJsonCount(1, 'result.content');
    }
})->with([
    'entry' => [fn (): ElementInterface => Entry::factory()->createElement(), 'entries', 'entry'],
    'draft entry' => [fn (): ElementInterface => app(Drafts::class)->createDraft(Entry::factory()->createElement()), 'entries', 'entry'],
    'disabled entry' => [function (): ElementInterface {
        $entry = Entry::factory()->disabled()->create();

        return app(Elements::class)->getElementById($entry->id, criteria: ['status' => null]);
    }, 'entries', 'entry'],
    'archived entry' => [function (): ElementInterface {
        $entry = Entry::factory()->archived()->create();

        return app(Elements::class)->getElementById($entry->id, criteria: ['archived' => true]);
    }, 'entries', 'entry', ['archived' => true]],
    'asset' => [fn (): ElementInterface => Asset::factory()->createElement(), 'assets', 'asset'],
    'address' => [fn (): ElementInterface => Address::factory()->withOwnedElement(Entry::factory()->createElement(), 1)->createElement(), 'addresses', 'address'],
    'user' => [fn (): ElementInterface => UserElement::find()->one(), 'users', 'user'],
    'trashed user' => [function (): ElementInterface {
        $user = User::factory()->create();
        $user->element->update(['dateDeleted' => now()]);

        return app(Elements::class)->getElementById($user->id, criteria: ['trashed' => true]);
    }, 'users', 'user', ['trashed' => true]],
]);

it('reads entry type field layouts through their owner identifiers and requires admin access', function (string $identifier): void {
    Edition::set(Edition::Pro);
    $actor = User::query()->firstOrFail();
    Passport::actingAs($actor, ['mcp:use'], 'craft-mcp');
    $field = Field::factory()->create(['handle' => 'summary', 'type' => PlainText::class]);
    $layout = FieldLayout::factory()->forField($field)->create();
    $entryType = EntryType::factory()->withFieldLayout($layout)->create(['handle' => 'article']);
    EntryTypes::refreshEntryTypes();
    $uri = 'craft://field-layouts/entry-types/'.$entryType->$identifier;

    McpRequest::send($this, 'resources/templates/list')
        ->assertOk()
        ->assertJsonFragment(['uriTemplate' => 'craft://field-layouts/entry-types/{entryType}']);

    $resource = McpRequest::send($this, 'resources/read', ['uri' => $uri])
        ->assertOk()
        ->assertJsonPath('result.contents.0.uri', $uri)
        ->json('result.contents.0.text');
    $data = json_decode($resource, true, flags: JSON_THROW_ON_ERROR);

    expect($data['fieldLayout']['id'])->toBe($layout->id)
        ->and($data['fieldLayout']['uid'])->toBe($layout->uid)
        ->and($data['fieldLayout']['config']['tabs'][0]['elements'][0]['fieldUid'])->toBe($field->uid);

    McpRequest::send($this, 'resources/read', ['uri' => 'craft://field-layouts/entry-types/nonexistent'])
        ->assertBadRequest()
        ->assertJsonPath('error.message', 'Entry type not found.');

    $actor->admin = false;
    $actor->save();
    app(UserPermissions::class)->saveUserPermissions($actor->id, ['accessCp', 'useCraftMcp']);
    Passport::actingAs($actor, ['mcp:use'], 'craft-mcp');

    McpRequest::send($this, 'resources/read', ['uri' => $uri])
        ->assertBadRequest()
        ->assertJsonStructure(['error' => ['code', 'message']])
        ->assertJsonMissingPath('result.contents');
})->with(['id', 'uid', 'handle']);
