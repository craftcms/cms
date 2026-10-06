<?php

declare(strict_types=1);

use CraftCms\Cms\Element\Drafts;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\ElementTypes;
use CraftCms\Cms\Element\Revisions;
use CraftCms\Cms\Mcp\Elements\BaseElementAdapter;
use CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry;
use CraftCms\Cms\Tests\Support\McpRequest;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User;
use Laravel\Passport\Passport;

it('continues past an invisible page and stops after the last result without exposing the lookahead record', function (string $tool): void {
    $key = openssl_pkey_new(['private_key_bits' => 2048]);
    config()->set('passport.public_key', openssl_pkey_get_details($key)['key']);
    Passport::actingAs(User::query()->firstOrFail(), ['mcp:use'], 'craft-mcp');
    app(ElementTypes::class)->register(TestPaginationElement::class);
    app(ElementAdapterRegistry::class)->register(TestPaginationAdapter::class);

    $owner = new TestPaginationElement(['title' => 'Owner']);
    app(Elements::class)->saveElement($owner);
    $ids = [];

    foreach (['Hidden pagination', 'First pagination', 'Last pagination'] as $title) {
        if ($tool === 'drafts.list') {
            $ids[] = app(Drafts::class)->createDraft($owner, newAttributes: ['title' => $title])->id;
        } elseif ($tool === 'revisions.list') {
            $ids[] = app(Revisions::class)->createRevision($owner, newAttributes: ['title' => $title], force: true);
        } else {
            $element = new TestPaginationElement(['title' => $title]);
            app(Elements::class)->saveElement($element);
            $ids[] = $element->id;
        }
    }

    $arguments = match ($tool) {
        'elements.list' => ['type' => 'pagination-items'],
        'search.query' => ['query' => 'pagination', 'types' => ['pagination-item']],
        default => ['type' => 'pagination-item', 'id' => $owner->id],
    };
    $call = fn (int $offset): array => McpRequest::send($this, 'tools/call', [
        'name' => $tool,
        'arguments' => [...$arguments, 'criteria' => ['limit' => 1, 'offset' => $offset, 'orderBy' => 'elements.id']],
    ])->assertOk()->assertJsonPath('result.isError', false)->json('result.structuredContent');
    $recordKey = match ($tool) {
        'elements.list' => 'elements',
        'drafts.list' => 'drafts',
        'revisions.list' => 'revisions',
        default => 'results',
    };
    $pageMetadata = static fn (array $page): array => $tool === 'search.query' ? $page['pagination']['pagination-item'] : $page;

    $hiddenPage = $call($tool === 'elements.list' ? 1 : 0);
    expect($hiddenPage[$recordKey])->toBe([])
        ->and($pageMetadata($hiddenPage)['nextOffset'])->toBe($tool === 'elements.list' ? 2 : 1);

    $firstPage = $call($pageMetadata($hiddenPage)['nextOffset']);
    $lastPage = $call($pageMetadata($firstPage)['nextOffset']);
    $firstRecord = $firstPage[$recordKey][0];
    $lastRecord = $lastPage[$recordKey][0];

    expect($firstPage[$recordKey])->toHaveCount(1)
        ->and($tool === 'search.query' ? $firstRecord['element']['id'] : $firstRecord['id'])->toBe($ids[1])
        ->and($lastPage[$recordKey])->toHaveCount(1)
        ->and($tool === 'search.query' ? $lastRecord['element']['id'] : $lastRecord['id'])->toBe($ids[2])
        ->and($pageMetadata($lastPage)['nextOffset'])->toBeNull()
        ->and($pageMetadata($lastPage)['limit'])->toBe(1);
})->with(['elements.list', 'drafts.list', 'revisions.list', 'search.query']);

class TestPaginationElement extends Element
{
    public static function refHandle(): string
    {
        return 'pagination-item';
    }

    public static function hasTitles(): bool
    {
        return true;
    }

    public function canView(UserElement $user): bool
    {
        return $this->title !== 'Hidden pagination';
    }
}

class TestPaginationAdapter extends BaseElementAdapter
{
    public static function handle(): string
    {
        return 'pagination-items';
    }

    public static function elementType(): string
    {
        return TestPaginationElement::class;
    }

    protected function updateAttributesSchema(): array
    {
        return ['type' => 'object', 'properties' => ['title' => ['type' => 'string']], 'additionalProperties' => false];
    }
}
