<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Entry\Data\EntryType;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\EntryTypes as EntryTypeService;
use CraftCms\Cms\Field\Enums\TranslationMethod;
use CraftCms\Cms\Field\Fields;
use CraftCms\Cms\FieldLayout\FieldLayout;
use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Shared\Enums\Color;
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
readonly class EntryTypes
{
    public function __construct(
        private EntryTypeService $entryTypes,
        private Fields $fields,
    ) {}

    /** @return array{count: int, entryTypes: list<array<string, mixed>>} */
    #[McpTool(
        name: 'entry-types.list',
        description: 'Lists Craft CMS entry types.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        $entryTypes = $this->entryTypes
            ->getAllEntryTypes()
            ->map($this->serialize(...))
            ->values();

        return [
            'count' => $entryTypes->count(),
            'entryTypes' => $entryTypes->all(),
        ];
    }

    /**
     * @param  int|null  $id  Entry type ID.
     * @param  string|null  $uid  Entry type UID.
     * @param  string|null  $handle  Entry type handle.
     * @return array{entryType: array<string, mixed>}
     */
    #[McpTool(
        name: 'entry-types.get',
        description: 'Gets a Craft CMS entry type by ID, UID, or handle.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        if (count(Arr::whereNotNull([$id, $uid, $handle])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid, handle.');
        }

        $entryType = $this->find($id, $uid, $handle);

        if (! $entryType) {
            throw new ToolCallException('Entry type not found.');
        }

        return ['entryType' => $this->serialize($entryType)];
    }

    /**
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config.
     * @return array{entryType: array<string, mixed>}
     */
    #[McpTool(name: 'entry-types.create', description: 'Creates a Craft CMS entry type.')]
    #[RequiresAdminChanges]
    public function create(
        string $name,
        string $handle,
        ?string $description = null,
        ?string $icon = null,
        ?Color $color = null,
        string $uiLabelFormat = '{title}',
        ?string $titleFormat = null,
        TranslationMethod $titleTranslationMethod = TranslationMethod::Site,
        ?string $titleTranslationKeyFormat = null,
        bool $allowLineBreaksInTitles = false,
        bool $showSlugField = true,
        TranslationMethod $slugTranslationMethod = TranslationMethod::Site,
        ?string $slugTranslationKeyFormat = null,
        bool $showStatusField = true,
        bool $showPostDateField = true,
        bool $showExpiryDateField = true,
        #[Schema(definition: [
            'type' => ['object', 'null'],
            'description' => 'Craft field layout config. Create fields before referencing their UIDs.',
            'additionalProperties' => true,
        ])]
        ?array $fieldLayout = null,
    ): array {
        $entryType = new EntryType([
            'name' => $name,
            'handle' => $handle,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'uiLabelFormat' => $uiLabelFormat,
            'titleFormat' => $titleFormat,
            'titleTranslationMethod' => $titleTranslationMethod,
            'titleTranslationKeyFormat' => $titleTranslationKeyFormat,
            'allowLineBreaksInTitles' => $allowLineBreaksInTitles,
            'showSlugField' => $showSlugField,
            'slugTranslationMethod' => $slugTranslationMethod,
            'slugTranslationKeyFormat' => $slugTranslationKeyFormat,
            'showStatusField' => $showStatusField,
            'showPostDateField' => $showPostDateField,
            'showExpiryDateField' => $showExpiryDateField,
        ]);

        if ($fieldLayout !== null) {
            $entryType->setFieldLayout($this->fieldLayout($fieldLayout));
        }

        if (! $this->entryTypes->saveEntryType($entryType)) {
            throw new ToolCallException($this->validationErrors($entryType));
        }

        return ['entryType' => $this->serialize($entryType)];
    }

    /**
     * @param  int|null  $id  Entry type ID.
     * @param  string|null  $uid  Entry type UID.
     * @param  string|null  $currentHandle  Existing entry type handle.
     * @param  array<string, mixed>|null  $fieldLayout  Native Craft field layout config.
     * @return array{entryType: array<string, mixed>}
     */
    #[McpTool(
        name: 'entry-types.update',
        description: 'Updates a Craft CMS entry type.',
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
        ?string $description = null,
        ?string $icon = null,
        ?Color $color = null,
        string $uiLabelFormat = '{title}',
        ?string $titleFormat = null,
        TranslationMethod $titleTranslationMethod = TranslationMethod::Site,
        ?string $titleTranslationKeyFormat = null,
        bool $allowLineBreaksInTitles = false,
        bool $showSlugField = true,
        TranslationMethod $slugTranslationMethod = TranslationMethod::Site,
        ?string $slugTranslationKeyFormat = null,
        bool $showStatusField = true,
        bool $showPostDateField = true,
        bool $showExpiryDateField = true,
        #[Schema(definition: [
            'type' => ['object', 'null'],
            'description' => 'Craft field layout config. Pass null to clear the layout.',
            'additionalProperties' => true,
        ])]
        ?array $fieldLayout = null,
    ): array {
        $entryType = $this->find($id, $uid, $currentHandle);

        if (! $entryType) {
            throw new ToolCallException('Entry type not found.');
        }

        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        Typecast::configure($entryType, array_intersect_key([
            'name' => $name,
            'handle' => $handle,
            'description' => $description,
            'icon' => $icon,
            'color' => $color,
            'uiLabelFormat' => $uiLabelFormat,
            'titleFormat' => $titleFormat,
            'titleTranslationMethod' => $titleTranslationMethod,
            'titleTranslationKeyFormat' => $titleTranslationKeyFormat,
            'allowLineBreaksInTitles' => $allowLineBreaksInTitles,
            'showSlugField' => $showSlugField,
            'slugTranslationMethod' => $slugTranslationMethod,
            'slugTranslationKeyFormat' => $slugTranslationKeyFormat,
            'showStatusField' => $showStatusField,
            'showPostDateField' => $showPostDateField,
            'showExpiryDateField' => $showExpiryDateField,
        ], $request->arguments));

        if (array_key_exists('fieldLayout', $request->arguments)) {
            $entryType->setFieldLayout($this->fieldLayout($fieldLayout ?? []));
        }

        if (! $this->entryTypes->saveEntryType($entryType)) {
            throw new ToolCallException($this->validationErrors($entryType));
        }

        return ['entryType' => $this->serialize($entryType)];
    }

    /**
     * @param  int|null  $id  Entry type ID.
     * @param  string|null  $uid  Entry type UID.
     * @param  string|null  $handle  Entry type handle.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'entry-types.delete',
        description: 'Deletes a Craft CMS entry type.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $handle = null,
    ): array {
        $entryType = $this->find($id, $uid, $handle);

        if (! $entryType) {
            throw new ToolCallException('Entry type not found.');
        }

        if (! $this->entryTypes->deleteEntryType($entryType)) {
            throw new ToolCallException('Entry type could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{count: int, entryTypes: list<array<string, mixed>>} */
    #[McpResource(
        uri: 'craft://entry-types',
        name: 'craft-entry-types',
        title: 'Craft Entry Types',
        description: 'A JSON list of Craft CMS entry types.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resource(): array
    {
        return $this->list();
    }

    /** @return array{entryType: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://entry-types/{entryType}',
        name: 'craft-entry-types-get',
        title: 'Craft Entry Type',
        description: 'A JSON Craft CMS entry type record addressed by entry type ID, UID, or handle.',
        mimeType: 'application/json',
    )]
    #[RequiresAdmin]
    public function resourceByIdentifier(string $entryType): array
    {
        $resolved = match (true) {
            ctype_digit($entryType) => $this->find(id: (int) $entryType),
            Str::isUuid($entryType) => $this->find(uid: $entryType),
            default => $this->find(handle: $entryType),
        };

        if (! $resolved) {
            throw new ResourceReadException('Entry type not found.');
        }

        return ['entryType' => $this->serialize($resolved)];
    }

    private function find(?int $id = null, ?string $uid = null, ?string $handle = null): ?EntryType
    {
        return match (true) {
            $id !== null => $this->entryTypes->getEntryTypeById($id),
            $uid !== null => $this->entryTypes->getEntryTypeByUid($uid),
            $handle !== null => $this->entryTypes->getEntryTypeByHandle($handle),
            default => null,
        };
    }

    /** @param array<string, mixed> $config */
    private function fieldLayout(array $config): FieldLayout
    {
        $layout = $this->fields->createLayout($config);
        $layout->type ??= Entry::class;

        return $layout;
    }

    /** @return array<string, mixed> */
    private function serialize(EntryType $entryType): array
    {
        $config = $entryType->getConfig();

        unset($config['fieldLayouts']);

        $layout = $entryType->getFieldLayout();

        return [
            'id' => $entryType->id,
            'uid' => $entryType->uid,
            ...$config,
            'fieldLayout' => [
                'id' => $layout->id,
                'uid' => $layout->uid,
                'type' => $layout->type,
                'config' => $layout->getConfig() ?? ['tabs' => []],
            ],
        ];
    }

    private function validationErrors(EntryType $entryType): string
    {
        return implode("\n", $entryType->errors()->all()) ?: 'Entry type could not be saved.';
    }
}
