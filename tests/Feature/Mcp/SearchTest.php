<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Mcp\Capabilities\Elements as ElementCapabilities;
use CraftCms\Cms\Section\Models\Section;
use CraftCms\Cms\Support\Facades\EntryTypes;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Illuminate\Http\Request;
use Laravel\Passport\Passport;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $user = User::query()->firstOrFail();
    actingAs($user);
    app(Request::class)->setUserResolver(static fn (): User => $user);

    $entryType = EntryType::factory()->create();
    $this->section = Section::factory()->withEntryTypes($entryType)->create();

    EntryTypes::refreshEntryTypes();
});

it('searches across element types through MCP', function () {
    $query = 'cross type needle';
    $user = User::query()->firstOrFail();

    $entry = app(ElementCapabilities::class)->create('entries', [
        'sectionId' => $this->section->id,
        'title' => $query,
        'enabled' => true,
    ])['element'];
    app(ElementCapabilities::class)->update('users', id: $user->id, attributes: ['firstName' => $query]);

    app(ElementTypes::class)->register(TestMcpSearchElement::class);
    $pluginElement = new TestMcpSearchElement(['title' => $query]);
    app(Elements::class)->saveElement($pluginElement);

    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs($user, ['mcp:use'], 'craft-mcp');

    $result = McpRequest::send($this, 'tools/call', [
        'name' => 'search.query',
        'arguments' => ['query' => $query, 'types' => ['entries', 'users', 'test-mcp-search-element']],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result');
    $search = $result['structuredContent'];
    $results = collect($search['results'])->keyBy('elementType');
    $links = collect($result['content'])->where('type', 'resource_link')->values();

    expect($search)->toMatchArray([
        'query' => $query,
        'types' => ['entry', 'user', 'test-mcp-search-element'],
        'count' => 3,
        'counts' => ['entry' => 1, 'user' => 1, 'test-mcp-search-element' => 1],
    ])
        ->and($results['entry']['element']['id'])->toBe($entry['id'])
        ->and($results['user']['element']['id'])->toBe($user->id)
        ->and($results['test-mcp-search-element']['element']['id'])->toBe($pluginElement->id)
        ->and($links->pluck('uri')->all())->toBe([
            "craft://entries/{$entry['id']}/sites/{$entry['siteId']}",
            "craft://users/$user->id",
        ]);

    foreach ($links as $link) {
        McpRequest::send($this, 'resources/read', ['uri' => $link['uri']])
            ->assertOk()
            ->assertJsonPath('result.contents.0.uri', $link['uri']);
    }
});

class TestMcpSearchElement extends Element
{
    #[Override]
    public static function refHandle(): string
    {
        return 'test-mcp-search-element';
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
}
