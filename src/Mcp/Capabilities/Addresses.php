<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Address\Addresses as AddressService;
use CraftCms\Cms\Address\Elements\Address;
use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\FieldLayoutConfig;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * Address-specific MCP capabilities. Addresses are listed and managed through the `elements.*` tools.
 *
 * @since 6.0.0
 */
readonly class Addresses
{
    public function __construct(
        private AddressService $addresses,
        private McpActor $actor,
        private ElementSerializer $elementSerializer,
        private Elements $elements,
        private FieldLayoutConfig $fieldLayouts,
    ) {}

    /** @return array{fieldLayout: array<string, mixed>} */
    #[McpTool(
        name: 'addresses.field-layout.get',
        description: 'Gets the Craft CMS address field layout.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function getFieldLayout(): array
    {
        return ['fieldLayout' => $this->fieldLayouts->serialize($this->addresses->getFieldLayout())];
    }

    /**
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config. Pass null to clear the layout.
     * @return array{fieldLayout: array<string, mixed>}
     */
    #[McpTool(
        name: 'addresses.field-layout.update',
        description: 'Updates the Craft CMS address field layout.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function updateFieldLayout(
        #[Schema(definition: FieldLayoutConfig::NullableSchema)]
        ?array $fieldLayout,
    ): array {
        $fieldLayout = $this->fieldLayouts->make(
            $fieldLayout ?? [],
            Address::class,
            $this->addresses->getFieldLayout(),
        );

        if (! $this->addresses->saveFieldLayout($fieldLayout)) {
            throw new ToolCallException(implode("\n", $fieldLayout->errors()->all()) ?: 'Address field layout could not be saved.');
        }

        return $this->getFieldLayout();
    }

    /** @return array{address: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: ElementResourceLinks::Templates[Address::class],
        name: 'craft-addresses-get',
        title: 'Craft Address',
        description: 'A JSON Craft CMS address record addressed by element ID and site ID.',
        mimeType: 'application/json',
    )]
    public function resourceByIdAndSite(int $id, int $siteId): array
    {
        $address = $this->elements->getElementById($id, Address::class, $siteId, [
            'status' => [Address::STATUS_ENABLED, Address::STATUS_DISABLED, Address::STATUS_ARCHIVED],
            'trashed' => null,
        ]);

        if (! $address || ! Gate::forUser($this->actor->user())->allows('view', $address)) {
            throw new ResourceReadException('Address not found.');
        }

        return ['address' => $this->elementSerializer->serialize($address)];
    }
}
