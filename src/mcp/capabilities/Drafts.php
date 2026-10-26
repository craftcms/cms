<?php

declare(strict_types=1);

namespace craft\mcp\capabilities;

use Craft;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\errors\InvalidElementException;
use craft\mcp\Authorization;
use craft\mcp\ElementQueryCriteria;
use craft\mcp\ElementQueryFactory;
use craft\mcp\McpActor;
use craft\mcp\serializers\ElementSerializer;
use craft\services\Drafts as DraftService;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 5.12.0
 */
readonly class Drafts
{
    private const CriteriaSchema = [
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
    ) {
    }

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

            if (!$canonical || !Authorization::allows($actor, 'view', $canonical)) {
                throw new ToolCallException('Canonical element not found.');
            }

            $query->draftOf($canonical);
        } elseif ($siteId !== null) {
            $query->siteId($siteId);
        }

        $page = $this->elementQueryCriteria->page($query, $criteria);
        $drafts = array_values(array_map(
            fn(ElementInterface $element): array => $this->elementSerializer->serialize($element, fields: $fields),
            array_filter($page['elements'], static fn(ElementInterface $element): bool => Authorization::allows($actor, 'view', $element)),
        ));

        return [
            'count' => count($drafts),
            'limit' => $page['limit'],
            'offset' => $page['offset'],
            'nextOffset' => $page['nextOffset'],
            'drafts' => $drafts,
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
            !$canonical
            || !Authorization::allows($actor, 'view', $canonical)
            || !Authorization::allows($actor, 'createDrafts', $canonical)
        ) {
            throw new ToolCallException('Canonical element not found.');
        }

        $draft = $this->drafts->createDraft(
            canonical: $canonical,
            creatorId: $actor->id,
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
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $draft = $this->findDraft($type, $id, $uid, $siteId);
        $actor = $this->actor->user();

        if (
            !$draft
            || !Authorization::allows($actor, 'save', $draft)
            || !Authorization::allows($actor, 'saveCanonical', $draft)
        ) {
            throw new ToolCallException('Draft not found.');
        }

        try {
            $element = $this->drafts->applyDraft($draft);
        } catch (InvalidElementException $exception) {
            throw new ToolCallException(
                implode("\n", $exception->element->getErrorSummary(true)) ?: 'Draft could not be applied.',
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
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $draft = $this->findDraft($type, $id, $uid, $siteId);

        if (!$draft || !Authorization::allows($this->actor->user(), 'delete', $draft)) {
            throw new ToolCallException('Draft not found.');
        }

        if (!Craft::$app->getElements()->deleteElement($draft)) {
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

        Craft::configure($query, Arr::whereNotNull([
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

        Craft::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        $element = $query->one();

        return $element instanceof Element && $element->getIsDraft() ? $element : null;
    }
}
