<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Elements;
use CraftCms\Cms\Element\UserInitiatedElementSave;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\CustomFieldSchema;
use CraftCms\Cms\Site\Sites;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\User\Contracts\CraftUser;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ResourceReadException;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use RuntimeException;

/**
 * @since 6.0.0
 */
readonly class Entries
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            ...ElementQueryCriteria::SchemaProperties,
            'slug' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Entry slug criteria.'],
            'section' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Section handle or handles.'],
            'sectionId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Section ID or IDs.'],
            'type' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Entry type handle or handles.'],
            'typeId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Entry type ID or IDs.'],
            'authorId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Author user ID or IDs.'],
        ],
        'additionalProperties' => true,
    ];

    private const array CreateAttributesSchema = [
        'type' => 'object',
        'properties' => [
            'sectionId' => ['type' => 'integer', 'description' => 'Section ID.'],
            'typeId' => ['type' => 'integer', 'description' => 'Entry type ID. Defaults to the section’s first entry type.'],
            'siteId' => ['type' => 'integer', 'description' => 'Site ID.'],
            'title' => ['type' => ['string', 'null'], 'description' => 'Entry title.'],
            'slug' => ['type' => ['string', 'null'], 'description' => 'Entry slug.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the entry is enabled.'],
            'authorId' => ['type' => ['integer', 'null'], 'description' => 'Author user ID. Defaults to the acting user.'],
            'authorIds' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Author user IDs.'],
            'postDate' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'Post date.'],
            'expiryDate' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'Expiry date.'],
            'parentId' => ['type' => ['integer', 'null'], 'description' => 'Parent entry ID for structured sections.'],
        ],
        'required' => ['sectionId'],
        'additionalProperties' => false,
    ];

    private const array UpdateAttributesSchema = [
        'type' => 'object',
        'properties' => [
            'typeId' => ['type' => 'integer', 'description' => 'Entry type ID.'],
            'title' => ['type' => ['string', 'null'], 'description' => 'Entry title.'],
            'slug' => ['type' => ['string', 'null'], 'description' => 'Entry slug.'],
            'enabled' => ['type' => 'boolean', 'description' => 'Whether the entry is enabled.'],
            'authorId' => ['type' => ['integer', 'null'], 'description' => 'Author user ID.'],
            'authorIds' => ['type' => 'array', 'items' => ['type' => 'integer'], 'description' => 'Author user IDs.'],
            'postDate' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'Post date.'],
            'expiryDate' => ['type' => ['string', 'null'], 'format' => 'date-time', 'description' => 'Expiry date.'],
            'parentId' => ['type' => ['integer', 'null'], 'description' => 'Parent entry ID for structured sections.'],
        ],
        'additionalProperties' => false,
    ];

    private const array FieldsSchema = [
        'type' => 'object',
        'description' => 'Custom field values keyed by field handle. Use entries.field-schema for the applicable schema.',
        'additionalProperties' => true,
    ];

    public function __construct(
        private McpActor $actor,
        private CustomFieldSchema $customFieldSchema,
        private Elements $elements,
        private ElementQueryCriteria $elementQueryCriteria,
        private Sites $sites,
        private UserInitiatedElementSave $userInitiatedElementSave,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria  Native Craft EntryQuery criteria. Custom field criteria may be passed by field handle.
     * @return array{count: int, limit: int, offset: int, entries: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'entries.list',
        description: 'Lists Craft CMS entries.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
    ): array {
        $actor = $this->actor->user();
        $query = Entry::find()->orderBy('elements.id');
        $criteria = $this->elementQueryCriteria->apply($query, $criteria);

        $entries = collect($query->all())
            ->filter(static fn (Entry $entry): bool => Gate::forUser($actor)->allows('view', $entry))
            ->values();

        return [
            'count' => $entries->count(),
            'limit' => $criteria['limit'],
            'offset' => $criteria['offset'],
            'entries' => $entries->map($this->serialize(...))->all(),
        ];
    }

    /**
     * @param  int|null  $id  Entry ID.
     * @param  string|null  $uid  Entry UID.
     * @param  int|null  $siteId  Site ID to load the entry in.
     * @return array{entry: array<string, mixed>}
     */
    #[McpTool(
        name: 'entries.get',
        description: 'Gets a Craft CMS entry by ID or UID.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $entry = $this->find($id, $uid, $siteId);

        if (! $entry || ! Gate::forUser($this->actor->user())->allows('view', $entry)) {
            throw new ToolCallException('Entry not found.');
        }

        return ['entry' => $this->serialize($entry)];
    }

    /**
     * Returns the writable custom-field schema for an existing entry or a new entry of the requested type.
     *
     * @return array{schema: array<string, mixed>}
     */
    #[McpTool(
        name: 'entries.field-schema',
        description: 'Gets the writable custom-field JSON Schema for an existing entry or an entry type.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function fieldSchema(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        ?int $sectionId = null,
        ?int $typeId = null,
    ): array {
        $existingEntry = $id !== null || $uid !== null;

        if ($existingEntry === ($sectionId !== null)) {
            throw new ToolCallException('Provide an entry ID or UID, or provide sectionId for a new entry.');
        }

        $entry = $existingEntry
            ? $this->find($id, $uid, $siteId)
            : new Entry;

        if (! $entry) {
            throw new ToolCallException('Entry not found.');
        }

        $actor = $this->actor->user();

        if ($existingEntry) {
            $this->authorizeSave($actor, $entry);

            if ($typeId !== null) {
                $entry->typeId = $typeId;
                $entry->fieldLayoutId = null;
            }
        } else {
            Typecast::configure($entry, Arr::whereNotNull([
                'sectionId' => $sectionId,
                'typeId' => $typeId,
                'siteId' => $siteId,
            ]));

            try {
                $entry->typeId ??= $entry->getAvailableEntryTypes()[0]->id;
            } catch (RuntimeException $exception) {
                throw new ToolCallException('Entry section or type is invalid.', previous: $exception);
            }

            $entry->fieldLayoutId = null;
            $entry->setAuthorId($actor->getCraftUserId());
        }

        $this->authorizeSave($actor, $entry);

        return ['schema' => $this->customFieldSchema->forElement($entry)];
    }

    /**
     * @param  array<string, mixed>  $attributes  Built-in entry attributes.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{entry: array<string, mixed>}
     */
    #[McpTool(name: 'entries.create', description: 'Creates a Craft CMS entry. Use entries.field-schema to discover custom fields.')]
    public function create(
        #[Schema(definition: self::CreateAttributesSchema)]
        array $attributes,
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $actor = $this->actor->user();
        $entry = new Entry;

        $this->populate($entry, $attributes, $fields, $actor);
        $this->authorizeSave($actor, $entry);

        return ['entry' => $this->save($entry, $actor)];
    }

    /**
     * @param  int|null  $id  Entry ID.
     * @param  string|null  $uid  Entry UID.
     * @param  int|null  $siteId  Site ID to load the entry in.
     * @param  array<string, mixed>  $attributes  Built-in entry attributes to update.
     * @param  array<string, mixed>  $fields  Custom field values keyed by field handle.
     * @return array{entry: array<string, mixed>}
     */
    #[McpTool(
        name: 'entries.update',
        description: 'Updates a Craft CMS entry. Use entries.field-schema to discover custom fields.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function update(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::UpdateAttributesSchema)]
        array $attributes = [],
        #[Schema(definition: self::FieldsSchema)]
        array $fields = [],
    ): array {
        $entry = $this->find($id, $uid, $siteId);

        if (! $entry) {
            throw new ToolCallException('Entry not found.');
        }

        $actor = $this->actor->user();

        $this->authorizeSave($actor, $entry);
        $this->populate($entry, $attributes, $fields, $actor);
        $this->authorizeSave($actor, $entry);

        return ['entry' => $this->save($entry, $actor)];
    }

    /**
     * @param  int|null  $id  Entry ID.
     * @param  string|null  $uid  Entry UID.
     * @param  int|null  $siteId  Site ID to load the entry in.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'entries.delete',
        description: 'Deletes a Craft CMS entry.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function delete(
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        bool $hardDelete = false,
    ): array {
        $entry = $this->find($id, $uid, $siteId);

        if (! $entry || ! Gate::forUser($this->actor->user())->allows('delete', $entry)) {
            throw new ToolCallException('Entry not found.');
        }

        if (! $this->elements->deleteElement($entry, $hardDelete)) {
            throw new ToolCallException('Entry could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array{entry: array<string, mixed>} */
    #[McpResourceTemplate(
        uriTemplate: 'craft://entries/{id}/sites/{siteId}',
        name: 'craft-entries-get',
        title: 'Craft Entry',
        description: 'A JSON Craft CMS entry record addressed by element ID and site ID.',
        mimeType: 'application/json',
    )]
    public function resourceByIdAndSite(int $id, int $siteId): array
    {
        $entry = $this->find(id: $id, siteId: $siteId);

        if (! $entry || ! Gate::forUser($this->actor->user())->allows('view', $entry)) {
            throw new ResourceReadException('Entry not found.');
        }

        return ['entry' => $this->serialize($entry)];
    }

    private function find(?int $id = null, ?string $uid = null, ?int $siteId = null): ?Entry
    {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = Entry::find()->status(null);

        Typecast::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        return $query->one();
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     */
    private function populate(Entry $entry, array $attributes, array $fields, CraftUser $actor): void
    {
        $authorIdProvided = array_key_exists('authorId', $attributes);
        $authorIdsProvided = array_key_exists('authorIds', $attributes);

        if ($authorIdProvided && $authorIdsProvided) {
            throw new ToolCallException('Provide only one of: authorId, authorIds.');
        }

        $authorId = Arr::pull($attributes, 'authorId');
        $authorIds = Arr::pull($attributes, 'authorIds');

        Typecast::configure($entry, $attributes);

        try {
            if (! $entry->typeId) {
                $entry->typeId = $entry->getAvailableEntryTypes()[0]->id;
            }
        } catch (RuntimeException $exception) {
            throw new ToolCallException('Entry section or type is invalid.', previous: $exception);
        }

        $entry->fieldLayoutId = null;

        if ($authorIdProvided || $authorIdsProvided) {
            if (! Gate::forUser($actor)->allows('changeAuthor', $entry)) {
                throw new ToolCallException('You are not authorized to change this entry author.');
            }

            $entry->setAuthorIds($authorIdsProvided ? $authorIds : $authorId);
        } elseif (! $entry->id && $actor->getCraftUserId() !== null) {
            $entry->setAuthorId($actor->getCraftUserId());
        }

        $entry->setFieldValues($fields);
    }

    private function authorizeSave(CraftUser $actor, Entry $entry): void
    {
        if ($this->sites->isMultiSite() && ! $actor->can("editSite:{$entry->getSite()->uid}")) {
            throw new ToolCallException('You are not authorized to edit entries for this site.');
        }

        if (! Gate::forUser($actor)->allows('save', $entry)) {
            throw new ToolCallException('You are not authorized to save this entry.');
        }
    }

    /** @return array<string, mixed> */
    private function save(Entry $entry, CraftUser $actor): array
    {
        try {
            $result = $this->userInitiatedElementSave->save($entry, $actor);
        } catch (LockTimeoutException $exception) {
            throw new ToolCallException('Could not acquire a lock to save the entry.', previous: $exception);
        }

        if (! $result->successful || ! $result->element instanceof Entry) {
            throw new ToolCallException(implode("\n", $result->element->errors()->all()) ?: 'Entry could not be saved.');
        }

        return $this->serialize($result->element);
    }

    /** @return array<string, mixed> */
    private function serialize(Entry $entry): array
    {
        return Arr::whereNotNull([
            'type' => $entry::class,
            ...$entry->toArray(),
        ]);
    }
}
