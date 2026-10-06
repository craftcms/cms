<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Exception\ResourceReadException;

/**
 * Entry-specific MCP capabilities. Entries are listed and managed through the `elements.*` tools.
 *
 * @since 6.0.0
 */
readonly class Entries
{
    public function __construct(
        private McpActor $actor,
        private ElementSerializer $elementSerializer,
        private Elements $elements,
    ) {}

    /** @return array{entry: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: ElementResourceLinks::Templates[Entry::class],
        name: 'craft-entries-get',
        title: 'Craft Entry',
        description: 'A JSON Craft CMS entry record addressed by element ID and site ID.',
        mimeType: 'application/json',
    )]
    public function resourceByIdAndSite(int $id, int $siteId): array
    {
        $entry = $this->elements->getElementById($id, Entry::class, $siteId, [
            'status' => [Entry::STATUS_ENABLED, Entry::STATUS_DISABLED, Entry::STATUS_ARCHIVED],
            'trashed' => null,
        ]);

        if (! $entry || ! Gate::forUser($this->actor->user())->allows('view', $entry)) {
            throw new ResourceReadException('Entry not found.');
        }

        return ['entry' => $this->elementSerializer->serialize($entry)];
    }
}
