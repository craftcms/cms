<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts as DraftService;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Models\Draft;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\Elements\ElementAdapter;
use CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Schema\CustomFieldSchema;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Mcp\Serializers\ReleaseSerializer;
use CraftCms\Cms\Release\Releases;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Facades\Sites;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Capability\Discovery\SchemaValidator;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * @since 6.0.0
 */
readonly class Drafts
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            'draftCreator' => [
                ...ElementQueryCriteria::IntegerOrIntegersSchema,
                'description' => 'Draft creator user ID or IDs.',
            ],
            'provisionalDrafts' => [
                'type' => ['boolean', 'null'],
                'description' => 'Whether to return provisional drafts, saved drafts, or both when null.',
            ],
            'savedDraftsOnly' => [
                'type' => 'boolean',
                'description' => 'Whether to return only saved unpublished drafts.',
            ],
            'orderBy' => ElementQueryCriteria::SchemaProperties['orderBy'],
            'offset' => ElementQueryCriteria::SchemaProperties['offset'],
            'limit' => ElementQueryCriteria::SchemaProperties['limit'],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(
        private DraftService $drafts,
        private ElementQueryFactory $elementQueries,
        private ElementQueryCriteria $elementQueryCriteria,
        private ElementSerializer $elementSerializer,
        private McpActor $actor,
        private Workflows $workflows,
        private Releases $releases,
        private ElementAdapterRegistry $adapters,
        private CustomFieldSchema $customFieldSchema,
        private SchemaValidator $schemaValidator,
        private ReleaseSerializer $releaseSerializer,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @param  list<string>|null  $fields
     * @return array{count: int, limit: int, offset: int, nextOffset: int|null, drafts: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'drafts.list',
        description: 'Lists Craft CMS drafts of existing elements for a supported element type. Unpublished drafts (new elements that have never been published) have no canonical element and are only listed with unpublished: true.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = [],
        #[Schema(description: 'List saved unpublished drafts instead of drafts of existing elements. Cannot be combined with id or uid.')]
        bool $unpublished = false,
    ): array {
        $actor = $this->actor->user();
        $query = $this->elementQueries->make($type)
            ->drafts()
            ->provisionalDrafts(null)
            ->draftOf($unpublished ? false : '*')
            ->status(null)
            ->orderBy('elements.id');

        if ($unpublished) {
            if ($id !== null || $uid !== null) {
                throw new ToolCallException('Unpublished drafts have no canonical element; omit id and uid when unpublished is true.');
            }

            $query->savedDraftsOnly();
        }

        if ($id !== null || $uid !== null) {
            $canonical = $this->findCanonical($type, $id, $uid, $siteId);

            if (! $canonical || ! Gate::forUser($actor)->allows('view', $canonical)) {
                $draft = $canonical ? null : $this->findDraft($type, $id, $uid, $siteId);

                if ($draft?->getIsUnpublishedDraft() && Gate::forUser($actor)->allows('view', $draft)) {
                    throw new ToolCallException("Element {$draft->id} is an unpublished draft and has no canonical element. Load it with elements.get, or list unpublished drafts with unpublished: true.");
                }

                throw new ToolCallException('Canonical element not found.');
            }

            $query->draftOf($canonical);
        } elseif ($siteId !== null) {
            $query->siteId($siteId);
        }

        $page = $this->elementQueryCriteria->page($query, $criteria);
        $drafts = collect($page['elements'])
            ->filter(static fn (ElementInterface $draft): bool => Gate::forUser($actor)->allows('view', $draft))
            ->map(fn (ElementInterface $draft): array => $this->elementSerializer->serialize($draft, fields: $fields))
            ->values();

        return [
            'count' => $drafts->count(),
            'limit' => $page['limit'],
            'offset' => $page['offset'],
            'nextOffset' => $page['nextOffset'],
            'drafts' => $drafts->all(),
        ];
    }

    /** @return array{draft: array<string, mixed>} */
    #[McpTool(name: 'drafts.create', description: 'Creates a draft of an existing Craft CMS element. Edit the draft with elements.update using the draft’s ID. To create a new entry as a draft, use elements.create with asUnpublishedDraft.')]
    public function create(
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        ?string $name = null,
        ?string $notes = null,
        bool $provisional = false,
    ): array {
        $canonical = $this->findCanonical($type, $id, $uid, $siteId);
        $actor = $this->actor->user();

        if (
            ! $canonical
            || ! Gate::forUser($actor)->allows('view', $canonical)
            || ! Gate::forUser($actor)->allows('createDrafts', $canonical)
        ) {
            throw new ToolCallException('Canonical element not found.');
        }

        $draft = $this->drafts->createDraft(
            canonical: $canonical,
            creatorId: $actor->getCraftUserId(),
            name: $name,
            notes: $notes,
            provisional: $provisional,
        );

        return ['draft' => $this->elementSerializer->serialize($draft)];
    }

    /**
     * @param  list<string>|null  $fields
     * @return array{draft: array<string, mixed>}
     */
    #[McpTool(name: 'drafts.get', description: 'Reads a draft by its draft record ID, not its element ID. siteId defaults to the current site.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function get(
        #[Schema(minimum: 1)]
        int $draftId,
        ?int $siteId = null,
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = null,
    ): array {
        return ['draft' => $this->elementSerializer->serialize($this->recordDraft($draftId, $siteId), fields: $fields)];
    }

    /** @return array{schema: array<string, mixed>} */
    #[McpTool(name: 'drafts.field-schema', description: 'Describes custom fields for an editable draft. Uses the registered element adapter.', annotations: new ToolAnnotations(readOnlyHint: true))]
    public function fieldSchema(#[Schema(minimum: 1)] int $draftId, ?int $siteId = null): array
    {
        $draft = $this->recordDraft($draftId, $siteId);
        $element = $this->draftAdapter($draft)->fieldSchemaElement($draft, [], $this->actor->user());

        return ['schema' => $this->customFieldSchema->forElement($element)];
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $fields
     * @return array{draft: array<string, mixed>}
     */
    #[McpTool(name: 'drafts.update', description: 'Edits an existing draft without publishing or creating another draft. Uses elements.schema updateAttributes and drafts.field-schema. Saving content can invalidate workflow approval.')]
    public function update(
        #[Schema(minimum: 1)]
        int $draftId,
        ?int $siteId = null,
        #[Schema(definition: ['type' => 'object', 'additionalProperties' => true])]
        array $attributes = [],
        #[Schema(definition: ['type' => 'object', 'additionalProperties' => true])]
        array $fields = [],
    ): array {
        $draft = $this->recordDraft($draftId, $siteId);
        $adapter = $this->draftAdapter($draft);
        $errors = $this->schemaValidator->validateAgainstJsonSchema($attributes, $adapter->schema()['updateAttributes']);

        if ($errors !== []) {
            throw new ToolCallException('Invalid draft attributes: '.$errors[0]['message'].'. Call elements.schema for accepted updateAttributes.');
        }

        return ['draft' => $adapter->update($draft, $attributes, $fields, $this->actor->user())['element']];
    }

    /** @return array{element: array<string, mixed>} */
    #[McpTool(
        name: 'drafts.apply',
        description: 'Applies a Craft CMS draft to its canonical element.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function apply(
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        ?string $type = null,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(minimum: 1, description: 'review.runId from a fresh workflows.review result. Provide together with workflowCurrentStage to reject stale approvals.')]
        ?int $workflowRunId = null,
        #[Schema(minimum: 0, description: 'Zero-based review.currentStage from the same workflows.review result. Provide together with workflowRunId; this is an index, not review.stage.uid.')]
        ?int $workflowCurrentStage = null,
        #[Schema(minimum: 1, description: 'Draft record ID. Mutually exclusive with the legacy element id/uid selectors; type is optional.')]
        ?int $draftId = null,
    ): array {
        if (($workflowRunId === null) !== ($workflowCurrentStage === null)) {
            throw new ToolCallException('Provide both workflowRunId and workflowCurrentStage, or neither.');
        }

        $draft = $this->findDraft($type, $id, $uid, $siteId, $draftId);
        $actor = $this->actor->user();

        if (
            ! $draft
            || ! Gate::forUser($actor)->allows('save', $draft)
            || ! Gate::forUser($actor)->allows('saveCanonical', $draft)
        ) {
            throw new ToolCallException('Draft not found.');
        }

        if ($workflowRunId !== null && ! $this->workflows->requiresApproval($draft)) {
            throw new ToolCallException('This draft no longer requires workflow approval. Refresh and try again.');
        }

        try {
            $apply = fn (ElementInterface $draft): ElementInterface => $this->drafts->applyDraft($draft, Arr::whereNotNull([
                'workflowRunId' => $workflowRunId,
                'workflowCurrentStage' => $workflowCurrentStage,
            ]));
            $release = $this->releases->releaseForDraft($draft);
            $element = $release === null
                ? $apply($draft)
                : $this->releases->publishAheadOfRelease($release->id, $draft, $actor, $apply);
        } catch (WorkflowException|HttpExceptionInterface $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        } catch (InvalidElementException $exception) {
            throw new ToolCallException(
                implode("\n", $exception->element->errors()->all()) ?: 'Draft could not be applied.',
                previous: $exception,
            );
        }

        return ['element' => $this->elementSerializer->serialize($element)];
    }

    /** @return array{deleted: true, release?: array<string, mixed>} */
    #[McpTool(
        name: 'drafts.delete',
        description: 'Deletes a Craft CMS draft.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function delete(
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        ?string $type = null,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(minimum: 1, description: 'Draft record ID. Mutually exclusive with the legacy element id/uid selectors; type is optional.')]
        ?int $draftId = null,
    ): array {
        $draft = $this->findDraft($type, $id, $uid, $siteId, $draftId);

        if (! $draft || ! Gate::forUser($this->actor->user())->allows('delete', $draft)) {
            throw new ToolCallException('Draft not found.');
        }

        $release = $this->releases->releaseForDraft($draft);

        try {
            $deleted = $this->drafts->discardDraft($draft);
        } catch (HttpExceptionInterface $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        if (! $deleted) {
            throw new ToolCallException('Draft could not be deleted.');
        }

        $release = $release?->fresh();

        return ['deleted' => true, ...($release === null ? [] : ['release' => $this->releaseSerializer->serialize($release)])];
    }

    private function findCanonical(
        string $type,
        ?int $id = null,
        ?string $uid = null,
        ?int $siteId = null,
    ): ?Element {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = $this->elementQueries->make($type)->status(null);

        Typecast::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        $element = $query->one();

        return $element instanceof Element ? $element : null;
    }

    private function findDraft(
        ?string $type,
        ?int $id = null,
        ?string $uid = null,
        ?int $siteId = null,
        ?int $draftId = null,
    ): ?Element {
        if (count(Arr::whereNotNull([$id, $uid, $draftId])) !== 1) {
            throw new ToolCallException('Provide exactly one of: draftId, id, uid.');
        }

        if ($draftId !== null) {
            $draft = $this->recordDraft($draftId, $siteId);
            if ($type !== null && $draft::class !== $this->elementQueries->resolve($type)) {
                throw new ToolCallException('The draft does not match the requested element type.');
            }

            return $draft;
        }

        if ($type === null) {
            throw new ToolCallException('Provide type when selecting a draft by element id or uid.');
        }

        $query = $this->elementQueries->make($type)
            ->drafts()
            ->provisionalDrafts(null)
            ->status(null);

        Typecast::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        $element = $query->one();

        return $element instanceof Element && $element->getIsDraft() ? $element : null;
    }

    private function recordDraft(int $draftId, ?int $siteId): Element
    {
        $siteId ??= Sites::getCurrentSite()->id;
        $draft = Draft::findElement($draftId, $siteId);

        if (! $draft instanceof Element || $draft->siteId !== $siteId || ! Gate::forUser($this->actor->user())->allows('view', $draft)) {
            throw new ToolCallException('Draft not found.');
        }

        return $draft;
    }

    private function draftAdapter(ElementInterface $draft): ElementAdapter
    {
        $type = $this->adapters->types()->first(fn (string $type): bool => $draft instanceof ($type::elementType()));

        return $type === null
            ? throw new ToolCallException('Editing this draft type is not supported. Register an element adapter.')
            : app($type);
    }
}
