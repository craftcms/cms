<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use CraftCms\Cms\Mcp\ElementQueryFactory;
use CraftCms\Cms\Mcp\ElementSerializer;
use CraftCms\Cms\Mcp\McpActor;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\Gate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\ToolAnnotations;

/**
 * @since 6.0.0
 */
readonly class Search
{
    private const int DefaultLimit = 25;

    private const array CriteriaSchema = [
        'type' => 'object',
        'properties' => [
            'site' => ElementQueryCriteria::SchemaProperties['site'],
            'siteId' => ElementQueryCriteria::SchemaProperties['siteId'],
            'status' => ElementQueryCriteria::SchemaProperties['status'],
            'relatedTo' => ElementQueryCriteria::SchemaProperties['relatedTo'],
            'with' => ElementQueryCriteria::SchemaProperties['with'],
            'archived' => ElementQueryCriteria::SchemaProperties['archived'],
            'orderBy' => ElementQueryCriteria::SchemaProperties['orderBy'],
            'offset' => ElementQueryCriteria::SchemaProperties['offset'],
            'limit' => [
                ...ElementQueryCriteria::SchemaProperties['limit'],
                'default' => self::DefaultLimit,
                'description' => 'Maximum results to return per element type.',
            ],
            'section' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Entry section handle or handles.'],
            'volume' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'Asset volume handle or handles.'],
            'folderId' => [...ElementQueryCriteria::IntegerOrIntegersSchema, 'description' => 'Asset folder ID or IDs.'],
            'group' => [...ElementQueryCriteria::StringOrStringsSchema, 'description' => 'User group handle or handles.'],
        ],
        'additionalProperties' => false,
    ];

    public function __construct(
        private ElementQueryFactory $elementQueries,
        private ElementQueryCriteria $elementQueryCriteria,
        private ElementSerializer $elementSerializer,
        private McpActor $actor,
    ) {}

    /**
     * @param  list<string>  $types  Registered element type reference handles or class names. Defaults to all registered types.
     * @param  array<string, mixed>  $criteria  Search criteria applied to each compatible element type.
     * @return array{query: string, types: list<string>, count: int, counts: array<string, int>, results: list<array{elementType: string, element: array<string, mixed>}>}
     */
    #[McpTool(
        name: 'search.query',
        description: 'Searches across registered Craft CMS element types using Craft search syntax.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    public function query(
        string $query,
        #[Schema(items: ['type' => 'string'])]
        array $types = [],
        #[Schema(definition: self::CriteriaSchema)]
        array $criteria = [],
    ): array {
        $query = trim($query);

        if ($query === '') {
            throw new ToolCallException('Provide a non-empty search query.');
        }

        $actor = $this->actor->user();
        $requestedTypes = $types !== [];
        $types = $this->resolveTypes($types ?: $this->elementQueries->registeredTypes());
        $userType = array_find($types, static fn (string $type): bool => is_a($type, User::class, true));

        if ($userType && ! $actor->can('viewUsers')) {
            if ($requestedTypes) {
                throw new ToolCallException('You are not authorized to search users.');
            }

            $types = array_values(array_diff($types, [$userType]));
        }

        if ($types === []) {
            throw new ToolCallException('No authorized element types to search.');
        }

        $results = [];
        $counts = [];
        $typeNames = [];

        foreach ($types as $type) {
            $typeName = $type::refHandle() ?? $type;
            $typeNames[] = $typeName;
            $typeCriteria = $this->criteriaForType($type, $criteria);
            $typeCriteria['search'] = $query;
            $typeCriteria['limit'] ??= self::DefaultLimit;
            $typeCriteria['orderBy'] ??= 'score desc';

            $elementQuery = $this->elementQueries->make($type);
            $this->elementQueryCriteria->apply($elementQuery, $typeCriteria);

            $elements = collect($elementQuery->all())
                ->filter(static fn (mixed $element): bool => $element instanceof ElementInterface
                    && Gate::forUser($actor)->allows('view', $element))
                ->values();

            $counts[$typeName] = $elements->count();

            foreach ($elements as $element) {
                $results[] = [
                    'elementType' => $typeName,
                    'element' => $this->elementSerializer->serialize($element),
                ];
            }
        }

        return [
            'query' => $query,
            'types' => $typeNames,
            'count' => count($results),
            'counts' => $counts,
            'results' => $results,
        ];
    }

    /**
     * @param  list<string>  $types
     * @return list<class-string<ElementInterface>>
     */
    private function resolveTypes(array $types): array
    {
        $resolved = [];

        foreach ($types as $type) {
            $elementType = $this->elementQueries->resolve($type);
            $resolved[$elementType] = $elementType;
        }

        return array_values($resolved);
    }

    /**
     * @param  class-string<ElementInterface>  $type
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed>
     */
    private function criteriaForType(string $type, array $criteria): array
    {
        if (! is_a($type, Entry::class, true)) {
            unset($criteria['section']);
        }

        if (! is_a($type, Asset::class, true)) {
            unset($criteria['volume'], $criteria['folderId']);
        }

        if (! is_a($type, User::class, true)) {
            unset($criteria['group']);
        }

        return $criteria;
    }
}
