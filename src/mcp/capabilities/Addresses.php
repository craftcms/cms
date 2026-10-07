<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\elements\Address;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\mcp\Authorization;
use craft\mcp\ElementResourceLinks;
use craft\mcp\McpActor;
use craft\mcp\schema\FieldLayoutConfig;
use craft\mcp\serializers\ElementSerializer;
use craft\services\Addresses as AddressService;
use craft\services\Elements;
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
    ) {
    }

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

        if (!$this->addresses->saveFieldLayout($fieldLayout)) {
            throw new ToolCallException(implode("\n", $fieldLayout->getErrorSummary(true)) ?: 'Address field layout could not be saved.');
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

        if (!$address || !Authorization::allows($this->actor->user(), 'view', $address)) {
            throw new ResourceReadException('Address not found.');
        }

        return ['address' => $this->elementSerializer->serialize($address)];
    }
}
