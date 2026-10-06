<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Elements as ElementService;
use CraftCms\Cms\Mcp\ElementLifecycle;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementResourceLinks;
use CraftCms\Cms\Mcp\Elements\ElementAdapter;
use CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\CustomFieldSchema;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\ToolAnnotations;

/**
 * Lists, reads, and manages elements of every type registered with {@see ElementAdapterRegistry}.
 *
 * Tool input schemas stay compact. Each element type's full criteria and attribute schemas are served by
 * `elements.schema` and the `craft://element-types/{type}` resource, and inputs are validated against them.
 *
 * @since 6.0.0
 */
readonly class Elements
{
    private const array TypeSchema = [
        'type' => 'string',
        'minLength' => 1,
        'description' => 'Element type: entries, assets, users, addresses, or a type a plugin adds. Read craft://element-types for every supported type.',
    ];

    private const array CriteriaSchema = [
        'type' => 'object',
        'description' => 'Element query criteria, such as {"sectionId": 1, "limit": 20}. Custom fields can be queried by handle. Call elements.schema for the criteria the type supports.',
        'additionalProperties' => true,
    ];

    private const array AttributesSchema = [
        'type' => 'object',
        'description' => 'Built-in element attributes. Call elements.schema for the attributes the type accepts.',
        'additionalProperties' => true,
    ];

    private const array FieldValuesSchema = [
        'type' => 'object',
        'description' => 'Custom field values keyed by field handle. Call elements.field-schema for the applicable schema.',
        'additionalProperties' => true,
    ];

    private const array ContextSchema = [
        'type' => 'object',
        'description' => 'For a new element, the values that select its field layout, such as {"sectionId": 1} for entries. Call elements.schema for the context the type accepts.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private McpActor $actor,
        private ElementAdapterRegistry $adapters,
        private CustomFieldSchema $customFieldSchema,
        private ElementService $elements,
        private ElementLifecycle $lifecycle,
        private ElementQueryCriteria $elementQueryCriteria,
        private ElementResourceLinks $resourceLinks,
        private SchemaValidator $schemaValidator,
    ) {}

    /** @return array<string, mixed> */
    #[McpTool(
        name: 'elements.schema',
        description: 'Describes an element type for the elements.* tools: the criteria, create and update attributes, field-schema context, and duplication modes it accepts. Also available as the craft://element-types/{type} resource.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function schema(
        #[Schema(definition: self::TypeSchema)]
        string $type,
    ): array {
        return $this->adapter($type)->schema();
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @param  list<string>|null  $fields
     */
    #[McpTool(
        name: 'elements.list',
        description: 'Lists elements of one type, such as entries, assets, users, or addresses.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = [],
    ): CallToolResult {
        $actor = $this->actor->user();
        $adapter = $this->adapter($type);
        $this->assertValid($criteria, $adapter->schema()['criteria'], 'criteria', $type);

        $query = $adapter->listQuery($actor);
        $page = $this->elementQueryCriteria->page($query, $criteria);

        $elements = collect($page['elements'])
            ->filter(static fn (mixed $element): bool => $adapter->canView($actor, $element))
            ->values();

        return $this->resourceLinks->result([
            'type' => $type,
            'count' => $elements->count(),
            'limit' => $page['limit'],
            'offset' => $page['offset'],
            'nextOffset' => $page['nextOffset'],
            'elements' => $elements->map(static fn (mixed $element): array => $adapter->serialize($element, $fields, summary: true))->all(),
        ], $elements);
    }

    /**
     * @param  int|null  $id  Element ID.
     * @param  string|null  $uid  Element UID.
     * @param  int|null  $siteId  Site ID to load the element in.
     * @param  list<string>|null  $fields
     * @return array{element: array<string, mixed>}
     */
    #[McpTool(
        name: 'elements.get',
        description: 'Gets an element by ID or UID.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = null,
    ): array {
        $adapter = $this->adapter($type);
        $element = $adapter->find($id, $uid, $siteId);

        if (! $element || ! $adapter->canView($this->actor->user(), $element)) {
            throw $this->notFound($adapter);
        }

        return ['element' => $adapter->serialize($element, $fields)];
    }

    /**
     * Returns the writable custom-field schema for an existing element, or for a new element described by the context.
     *
     * @param  int|null  $id  Existing element ID.
     * @param  string|null  $uid  Existing element UID.
     * @param  int|null  $siteId  Site ID to load an existing element in.
     * @param  array<string, mixed>  $context
     * @return array{schema: array<string, mixed>}
     */
    #[McpTool(
        name: 'elements.field-schema',
        description: 'Gets the writable custom-field JSON Schema for an existing element, or for a new element described by context.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function fieldSchema(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::ContextSchema)]
        array $context = [],
    ): array {
        $actor = $this->actor->user();
        $adapter = $this->adapter($type);
        $contextSchema = $adapter->schema()['fieldSchemaContext'] ?? null;

        if ($contextSchema === null && $context !== []) {
            throw new ToolCallException(sprintf('%s do not accept a field-schema context.', ucfirst($adapter::elementType()::pluralLowerDisplayName())));
        }

        if ($contextSchema !== null) {
            $this->assertValid($context, $contextSchema, 'context', $type);
        }

        $element = null;

        if ($id !== null || $uid !== null) {
            $element = $adapter->find($id, $uid, $siteId) ?? throw $this->notFound($adapter);
        }

        return ['schema' => $this->customFieldSchema->forElement($adapter->fieldSchemaElement($element, $context, $actor))];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'elements.create',
        description: 'Creates an element. Call elements.field-schema to discover custom fields. For assets, use assets.create instead.',
    )]
    public function create(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        #[Schema(definition: self::AttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldValuesSchema)]
        array $fields = [],
    ): array {
        $actor = $this->actor->user();
        $adapter = $this->adapter($type);
        $attributesSchema = $adapter->schema()['createAttributes'] ?? null;

        if ($attributesSchema !== null) {
            $this->assertValid($attributes, $attributesSchema, 'attributes', $type);
        }

        return $adapter->create($attributes, $fields, $actor);
    }

    /**
     * @param  int|null  $id  Element ID.
     * @param  string|null  $uid  Element UID.
     * @param  int|null  $siteId  Site ID to load the element in.
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    #[McpTool(
        name: 'elements.update',
        description: 'Updates an element. Call elements.field-schema to discover custom fields.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function update(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::AttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldValuesSchema)]
        array $fields = [],
    ): array {
        $actor = $this->actor->user();
        $adapter = $this->adapter($type);
        $this->assertValid($attributes, $adapter->schema()['updateAttributes'], 'attributes', $type);

        $element = $adapter->find($id, $uid, $siteId) ?? throw $this->notFound($adapter);

        return $adapter->update($element, $attributes, $fields, $actor);
    }

    /**
     * @param  int|null  $id  Element ID.
     * @param  string|null  $uid  Element UID.
     * @param  int|null  $siteId  Site ID to load the element in.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'elements.delete',
        description: 'Deletes an element. Soft-deleted elements can be restored with elements.restore unless hardDelete is true.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function delete(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): array {
        $adapter = $this->adapter($type);
        $element = $adapter->find($id, $uid, $siteId);

        if (! $element || ! $adapter->canDelete($this->actor->user(), $element)) {
            throw $this->notFound($adapter);
        }

        if (! $this->elements->deleteElement($element, $hardDelete)) {
            throw new ToolCallException($element::displayName().' could not be deleted.');
        }

        return ['deleted' => true];
    }

    /**
     * @param  int|null  $id  Element ID.
     * @param  string|null  $uid  Element UID.
     * @param  int|null  $siteId  Site ID to load the element in. It does not limit restoration.
     * @return array{restored: bool}
     */
    #[McpTool(
        name: 'elements.restore',
        description: 'Restores a deleted element across all supported sites. Returns restored: false without changes if it is already active. List deleted elements with criteria {"trashed": true, "status": null}.',
        annotations: new ToolAnnotations(destructiveHint: true, idempotentHint: true),
    )]
    public function restore(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $adapter = $this->adapter($type);
        $element = $this->lifecycle->find($adapter::elementType(), $id, $uid, $siteId, includeTrashed: true, ability: 'save');

        return $this->lifecycle->restore($element);
    }

    /**
     * @param  int|null  $id  Element ID.
     * @param  string|null  $uid  Element UID.
     * @param  int|null  $siteId  Site ID to load the element in.
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     * @return array{valid: bool, scenario: string, errors: array<string, list<string>>}
     */
    #[McpTool(
        name: 'elements.validate',
        description: 'Validates an existing element or saved draft under live rules, optionally applying proposed attributes and fields in memory. Does not save. A valid result does not guarantee a later update succeeds.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function validate(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::AttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldValuesSchema)]
        array $fields = [],
    ): array {
        $adapter = $this->adapter($type);
        $this->assertValid($attributes, $adapter->schema()['updateAttributes'], 'attributes', $type);

        $element = $this->lifecycle->find($adapter::elementType(), $id, $uid, $siteId);

        if ($attributes !== [] || $fields !== []) {
            $adapter->prepareValidation($element, $attributes, $fields, $this->actor->user());
        }

        return $this->lifecycle->validate($element);
    }

    /**
     * @param  int|null  $id  Element ID.
     * @param  string|null  $uid  Element UID.
     * @param  int|null  $siteId  Site ID to load the element in.
     * @param  string|null  $mode  Duplication mode. Call elements.schema for the modes the type supports.
     * @return array{element: array<string, mixed>}
     */
    #[McpTool(
        name: 'elements.duplicate',
        description: 'Duplicates an element as independent content, for types that support it. Call elements.schema for the type’s duplication modes.',
    )]
    public function duplicate(
        #[Schema(definition: self::TypeSchema)]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        ?string $mode = null,
    ): array {
        $adapter = $this->adapter($type);
        $element = $this->lifecycle->find($adapter::elementType(), $id, $uid, $siteId);

        return ['element' => $adapter->serialize($adapter->duplicate($element, $mode))];
    }

    /** @return array{count: int, types: list<array{type: string, name: string, elementType: string, schema: string}>} */
    #[McpResource(
        uri: 'craft://element-types',
        name: 'craft-element-types',
        title: 'Craft Element Types',
        description: 'A JSON list of the element types the elements.* tools support.',
        mimeType: 'application/json',
    )]
    public function typesResource(): array
    {
        $types = array_map(function (string $handle): array {
            $schema = $this->adapter($handle)->schema();

            return [
                'type' => $schema['type'],
                'name' => $schema['name'],
                'elementType' => $schema['elementType'],
                'schema' => "craft://element-types/{$handle}",
            ];
        }, $this->adapters->handles());

        return ['count' => count($types), 'types' => $types];
    }

    /** @return array<string, mixed> */
    #[McpResourceTemplate(
        uriTemplate: 'craft://element-types/{type}',
        name: 'craft-element-types-get',
        title: 'Craft Element Type',
        description: 'A JSON description of the criteria, attributes, field-schema context, and duplication modes an element type accepts in the elements.* tools.',
        mimeType: 'application/json',
    )]
    public function typeResource(string $type): array
    {
        $adapter = $this->adapters->find($type);

        if ($adapter === null) {
            throw new ResourceReadException("Unsupported element type [$type].");
        }

        return $adapter->schema();
    }

    private function adapter(string $type): ElementAdapter
    {
        return $this->adapters->find($type) ?? throw new ToolCallException(sprintf(
            'Unsupported element type [%s]. Supported types: %s.',
            $type,
            implode(', ', $this->adapters->handles()),
        ));
    }

    private function notFound(ElementAdapter $adapter): ToolCallException
    {
        return new ToolCallException($adapter::elementType()::displayName().' not found.');
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, mixed>  $schema
     */
    private function assertValid(array $values, array $schema, string $parameter, string $type): void
    {
        $errors = $this->schemaValidator->validateAgainstJsonSchema($values, $schema);

        if ($errors === []) {
            return;
        }

        $messages = array_map(
            static fn (array $error): string => (in_array($error['pointer'], ['', '/'], true) ? '' : "{$error['pointer']}: ").$error['message'],
            array_slice($errors, 0, 3),
        );

        throw new ToolCallException(sprintf(
            'Invalid %s for %s: %s. Call elements.schema for the accepted %s.',
            $parameter,
            $type,
            implode('; ', $messages),
            $parameter,
        ));
    }
}
