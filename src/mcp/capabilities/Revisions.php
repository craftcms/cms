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
use craft\services\Revisions as RevisionService;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Revisions
{
    private const CriteriaSchema = [
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
    ) {
    }

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
            ->revisions()
            ->status(null)
            ->orderBy('elements.id');

        if ($id !== null || $uid !== null) {
            $canonical = $this->findCanonical($type, $id, $uid, $siteId);

            if (!$canonical || !Authorization::allows($actor, 'view', $canonical)) {
                throw new ToolCallException('Canonical element not found.');
            }

            $query->revisionOf($canonical);
        } elseif ($siteId !== null) {
            $query->siteId($siteId);
        }

        $page = $this->elementQueryCriteria->page($query, $criteria);
        $revisions = array_values(array_map(
            fn(ElementInterface $element): array => $this->elementSerializer->serialize($element, fields: $fields),
            array_filter($page['elements'], static fn(ElementInterface $element): bool => Authorization::allows($actor, 'view', $element)),
        ));

        return [
            'count' => count($revisions),
            'limit' => $page['limit'],
            'offset' => $page['offset'],
            'nextOffset' => $page['nextOffset'],
            'revisions' => $revisions,
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
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
        #[Schema(definition: ElementSerializer::FieldsSchema)]
        ?array $fields = null,
    ): array {
        $revision = $this->findRevision($type, $id, $uid, $siteId);

        if (!$revision || !Authorization::allows($this->actor->user(), 'view', $revision)) {
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
        #[Schema(description: 'Registered element reference handle or PHP class; see craft://element-types and info.get.')]
        string $type,
        ?int $id = null,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?int $siteId = null,
    ): array {
        $revision = $this->findRevision($type, $id, $uid, $siteId);
        $actor = $this->actor->user();

        if (!$revision || !Authorization::allows($actor, 'saveCanonical', $revision)) {
            throw new ToolCallException('Revision not found.');
        }

        $creatorId = $actor->id;

        if ($creatorId === null) {
            throw new ToolCallException('Could not determine the revision creator.');
        }

        try {
            $element = $this->revisions->revertToRevision($revision, $creatorId);
        } catch (InvalidElementException $exception) {
            throw new ToolCallException(
                implode("\n", $exception->element->getErrorSummary(true)) ?: 'Revision could not be applied.',
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

        Craft::configure($query, Arr::whereNotNull([
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

        Craft::configure($query, Arr::whereNotNull([
            'id' => $id,
            'uid' => $uid,
            'siteId' => $siteId,
        ]));

        $element = $query->one();

        return $element instanceof Element && $element->getIsRevision() ? $element : null;
    }
}
