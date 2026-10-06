<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Element;
use CraftCms\Cms\Element\Exceptions\InvalidElementException;
use CraftCms\Cms\Element\Revisions as RevisionService;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\Mcp\Serializers\ElementSerializer;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Revisions
{
    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            'revisionCreator' => [
                'type' => 'integer',
                'description' => 'Revision creator user ID.',
            ],
            'orderBy' => ElementQueryCriteria::SchemaProperties['orderBy'],
            'offset' => ElementQueryCriteria::SchemaProperties['offset'],
            'limit' => ElementQueryCriteria::SchemaProperties['limit'],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(
        private RevisionService $revisions,
        private ElementQueryFactory $elementQueries,
        private ElementQueryCriteria $elementQueryCriteria,
        private ElementSerializer $elementSerializer,
        private McpActor $actor,
    ) {}

    /**
     * @param  array<string, mixed>  $criteria
     * @param  list<string>|null  $fields
     * @return array{count: int, limit: int, offset: int, nextOffset: int|null, revisions: list<array<string, mixed>>}
     */
    #[McpTool(
        name: 'revisions.list',
        description: 'Lists Craft CMS revisions for a registered element type.',
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
            ->revisions()
            ->status(null)
            ->orderBy('elements.id');

        if ($id !== null || $uid !== null) {
            $canonical = $this->findCanonical($type, $id, $uid, $siteId);

            if (! $canonical || ! Gate::forUser($actor)->allows('view', $canonical)) {
                throw new ToolCallException('Canonical element not found.');
            }

            $query->revisionOf($canonical);
        } elseif ($siteId !== null) {
            $query->siteId($siteId);
        }

        $page = $this->elementQueryCriteria->page($query, $criteria);
        $revisions = collect($page['elements'])
            ->filter(static fn (ElementInterface $revision): bool => Gate::forUser($actor)->allows('view', $revision))
            ->map(fn (ElementInterface $revision): array => $this->elementSerializer->serialize($revision, fields: $fields))
            ->values();

        return [
            'count' => $revisions->count(),
            'limit' => $page['limit'],
            'offset' => $page['offset'],
            'nextOffset' => $page['nextOffset'],
            'revisions' => $revisions->all(),
        ];
    }

    /**
     * @param  list<string>|null  $fields
     * @return array{revision: array<string, mixed>}
     */
    #[McpTool(
        name: 'revisions.get',
        description: 'Gets a Craft CMS revision by ID or UID.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function get(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = null,
    ): array {
        $revision = $this->findRevision($type, $id, $uid, $siteId);

        if (! $revision || ! Gate::forUser($this->actor->user())->allows('view', $revision)) {
            throw new ToolCallException('Revision not found.');
        }

        return ['revision' => $this->elementSerializer->serialize($revision, fields: $fields)];
    }

    /** @return array{element: array<string, mixed>} */
    #[McpTool(
        name: 'revisions.apply',
        description: 'Applies a Craft CMS revision to its canonical element.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    public function apply(
        #[Schema(description: 'Registered element type reference handle or class name.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $revision = $this->findRevision($type, $id, $uid, $siteId);
        $actor = $this->actor->user();

        if (! $revision || ! Gate::forUser($actor)->allows('saveCanonical', $revision)) {
            throw new ToolCallException('Revision not found.');
        }

        $creatorId = $actor->getCraftUserId();

        if ($creatorId === null) {
            throw new ToolCallException('Could not determine the revision creator.');
        }

        try {
            $element = $this->revisions->revertToRevision($revision, $creatorId);
        } catch (InvalidElementException $exception) {
            throw new ToolCallException(
                implode("\n", $exception->element->errors()->all()) ?: 'Revision could not be applied.',
                previous: $exception,
            );
        }

        return ['element' => $this->elementSerializer->serialize($element)];
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

    private function findRevision(
        string $type,
        ?int $id = null,
        ?string $uid = null,
        ?int $siteId = null,
    ): ?Element {
        if (count(Arr::whereNotNull([$id, $uid])) !== 1) {
            throw new ToolCallException('Provide exactly one of: id, uid.');
        }

        $query = $this->elementQueries->make($type)
            ->revisions()
            ->status(null);

        Typecast::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        $element = $query->one();

        return $element instanceof Element && $element->getIsRevision() ? $element : null;
    }
}
