<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Drafts as DraftService;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use CraftCms\Cms\Workflow\Exceptions\WorkflowException;
use CraftCms\Cms\Workflow\Workflows;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

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
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @param  list<string>|null  $fields
     * @return array{count: int, limit: int, offset: int, nextOffset: int|null, drafts: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'drafts.list',
        description: 'Lists Craft CMS drafts for a supported element type.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function list(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = [],
    ): array {
        $actor = $this->actor->user();
        $query = $this->elementQueries->make($type)
            ->drafts()
            ->provisionalDrafts(null)
            ->draftOf('*')
            ->status(null)
            ->orderBy('elements.id');

        if ($id !== null || $uid !== null) {
            $canonical = $this->findCanonical($type, $id, $uid, $siteId);

            if (! $canonical || ! Gate::forUser($actor)->allows('view', $canonical)) {
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
    #[McpTool(name: 'drafts.create', description: 'Creates a draft of a Craft CMS element.')]
    public function create(
        #[Schema(description: 'Registered element type reference handle or class name.')]
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

    /** @return array{element: array<string, mixed>} */
    #[McpTool(
        name: 'drafts.apply',
        description: 'Applies a Craft CMS draft to its canonical element.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function apply(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(minimum: 1, description: 'Expected workflow run ID. Provide together with workflowCurrentStage to reject stale approvals.')]
        ?int $workflowRunId = null,
        #[Schema(minimum: 0, description: 'Expected zero-based workflow stage index. Provide together with workflowRunId.')]
        ?int $workflowCurrentStage = null,
    ): array {
        if (($workflowRunId === null) !== ($workflowCurrentStage === null)) {
            throw new ToolCallException('Provide both workflowRunId and workflowCurrentStage, or neither.');
        }

        $draft = $this->findDraft($type, $id, $uid, $siteId);
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
            $element = $this->drafts->applyDraft($draft, Arr::whereNotNull([
                'workflowRunId' => $workflowRunId,
                'workflowCurrentStage' => $workflowCurrentStage,
            ]));
        } catch (WorkflowException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        } catch (InvalidElementException $exception) {
            throw new ToolCallException(
                implode("\n", $exception->element->errors()->all()) ?: 'Draft could not be applied.',
                previous: $exception,
            );
        }

        return ['element' => $this->elementSerializer->serialize($element)];
    }

    /** @return array{deleted: true} */
    #[McpTool(
        name: 'drafts.delete',
        description: 'Deletes a Craft CMS draft.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function delete(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $draft = $this->findDraft($type, $id, $uid, $siteId);

        if (! $draft || ! Gate::forUser($this->actor->user())->allows('delete', $draft)) {
            throw new ToolCallException('Draft not found.');
        }

        if (! $this->drafts->discardDraft($draft)) {
            throw new ToolCallException('Draft could not be deleted.');
        }

        return ['deleted' => true];
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
        string $type,
        ?int $id = null,
        ?string $uid = null,
        ?int $siteId = null,
    ): ?Element {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
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
}
