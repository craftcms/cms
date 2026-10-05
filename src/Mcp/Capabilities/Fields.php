<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Component\ComponentHelper;
use CraftCms\Cms\Field\Contracts\FieldInterface;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields as FieldService;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Typecast;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class Fields
{
    public function __construct(private FieldService $fields) {}

    /** @return array{count: int, fields: list<array<string, mixed>>} */
    #[McpTool(
        name: 'fields.list',
        description: 'Lists Craft CMS fields.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        $fields = $this->fields
            ->getAllFields()
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $fields->count(),
            'fields' => $fields->all(),
        ];
    }

    /**
     * @param  int|null  $id  Field ID.
     * @param  string|null  $uid  Field UID.
     * @param  string|null  $handle  Field handle.
     * @return array{field: array<string, mixed>}
     */
    #[McpTool(
        name: 'fields.get',
        description: 'Gets a Craft CMS field by ID, UID, or handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $field = $this->find($id, $uid, $handle);

        if (! $field) {
            throw new ToolCallException('Field not found.');
        }

        return ['field' => $this->serialize($field)];
    }

    /**
     * @param  string  $type  Registered field class name, such as CraftCms\Cms\Field\PlainText.
     * @param  array<string, mixed>  $settings  Type-specific field settings.
     * @return array{field: array<string, mixed>}
     */
    #[McpTool(name: 'fields.create', description: 'Creates a Craft CMS field.')]
    #[RequiresAdminChanges]
    public function create(
        string $type,
        string $name,
        string $handle,
        ?string $instructions = null,
        bool $searchable = true,
        TranslationMethod $translationMethod = TranslationMethod::None,
        ?string $translationKeyFormat = null,
        #[Schema(type: 'object', additionalProperties: true)]
        array $settings = [],
    ): array {
        if (! ComponentHelper::validateComponentClass($type, FieldInterface::class) || ! $type::isSelectable()) {
            throw new ToolCallException('Field type is invalid or unavailable.');
        }

        $field = $this->fields->createField([
            'type' => $type,
            'name' => $name,
            'handle' => $handle,
            'instructions' => $instructions,
            'searchable' => $searchable,
            'translationMethod' => $translationMethod,
            'translationKeyFormat' => $translationKeyFormat,
            'settings' => $settings,
        ]);

        return ['field' => $this->save($field)];
    }

    /**
     * @param  int|null  $id  Field ID.
     * @param  string|null  $uid  Field UID.
     * @param  string|null  $currentHandle  Existing field handle.
     * @param  string|null  $name  Field name.
     * @param  string|null  $handle  New field handle.
     * @param  string|null  $instructions  Field instructions.
     * @param  bool  $searchable  Whether the field contributes search keywords.
     * @param  TranslationMethod  $translationMethod  Field translation method.
     * @param  string|null  $translationKeyFormat  Custom translation key format.
     * @param  array<string, mixed>  $settings  Type-specific field settings to update.
     * @return array{field: array<string, mixed>}
     */
    #[McpTool(
        name: 'fields.update',
        description: 'Updates a Craft CMS field. Changing its field type is not supported.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $currentHandle = null,
        ?string $name = null,
        ?string $handle = null,
        ?string $instructions = null,
        bool $searchable = true,
        TranslationMethod $translationMethod = TranslationMethod::None,
        ?string $translationKeyFormat = null,
        #[Schema(type: 'object', additionalProperties: true)]
        array $settings = [],
    ): array {
        $field = $this->find($id, $uid, $currentHandle);

        if (! $field) {
            throw new ToolCallException('Field not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        $attributes = array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'instructions' => $instructions,
            'searchable' => $searchable,
            'translationMethod' => $translationMethod,
            'translationKeyFormat' => $translationKeyFormat,
        ], $request->arguments);

        if (array_key_exists('settings', $request->arguments)) {
            $attributes = [...array_replace($field->getSettings(), $settings), ...$attributes];
        }

        Typecast::configure($field, $attributes);

        return ['field' => $this->save($field)];
    }

    /**
     * @param  int|null  $id  Field ID.
     * @param  string|null  $uid  Field UID.
     * @param  string|null  $handle  Field handle.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'fields.delete',
        description: 'Deletes a Craft CMS field.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $field = $this->find($id, $uid, $handle);

        if (! $field) {
            throw new ToolCallException('Field not found.');
        }

        if (! $this->fields->deleteField($field)) {
            throw new ToolCallException('Field could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{count: int, fields: list<array<string, mixed>>} */
    #[McpResource(
        uri: 'craft://fields',
        name: 'craft-fields',
        title: 'Craft Fields',
        description: 'A JSON list of Craft CMS fields.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resource(): array
    {
        return $this->list();
    }

    /** @return array{field: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://fields/{field}',
        name: 'craft-fields-get',
        title: 'Craft Field',
        description: 'A JSON Craft CMS field record addressed by field ID, UID, or handle.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resourceByIdentifier(string $field): array
    {
        $resolved = match (true) {
            ctype_digit($field) => $this->find(id: (int) $field),
            Str::isUuid($field) => $this->find(uid: $field),
            default => $this->find(handle: $field),
        };

        if (! $resolved) {
            throw new ResourceReadException('Field not found.');
        }

        return ['field' => $this->serialize($resolved)];
    }

    private function find(?int $id = null, ?string $uid = null, ?string $handle = null): ?FieldInterface
    {
        if (count(Arr::whereNotNull([$id, $uid, $handle])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, handle.');
        }

        return match (true) {
            $id !== null => $this->fields->getFieldById($id),
            $uid !== null => $this->fields->getFieldByUid($uid),
            $handle !== null => $this->fields->getFieldByHandle($handle, false),
            default => null,
        };
    }

    /** @return array<string, mixed> */
    private function save(FieldInterface $field): array
    {
        if (! $this->fields->saveField($field)) {
            throw new ToolCallException(implode("\n", $field->errors()->all()) ?: 'Field could not be saved.');
        }

        return $this->serialize($field);
    }

    /** @return array<string, mixed> */
    private function serialize(FieldInterface $field): array
    {
        $config = $this->fields->createFieldConfig($field);

        return [
            'id' => $field->getId(),
            'uid' => $field->uid,
            ...$config,
            'displayName' => $field::displayName(),
            'phpType' => $field::phpType(),
            'dbType' => $field::dbType(),
        ];
    }
}
