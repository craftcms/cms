<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Mcp\Attributes\Mcp;
use CraftCms\Cms\Mcp\Schema\ElementSchemaTypes;
use CraftCms\Cms\Support\Typecast;
use Illuminate\Support\Arr;
use LogicException;
use Mcp\Exception\ToolCallException;

/**
 * @since 6.0.0
 */
class ElementQueryCriteria
{
    public const int DefaultLimit = 100;

    public const int MaxLimit = 500;

    public const array IntegerOrIntegersSchema = [
        'anyOf' => [
            ['type' => 'integer'],
            ['type' => 'array', 'items' => ['type' => 'integer']],
        ],
    ];

    public const array StringOrStringsSchema = [
        'anyOf' => [
            ['type' => 'string'],
            ['type' => 'array', 'items' => ['type' => 'string']],
        ],
    ];

    /** Paging and sorting properties that MCP tools add to element query params. */
    public const array SchemaProperties = [
        'orderBy' => [
            'type' => 'string',
            'description' => 'Native Craft element order expression.',
        ],
        'offset' => [
            'type' => 'integer',
            'minimum' => 0,
            'default' => 0,
            'description' => 'Number of elements to skip.',
        ],
        'limit' => [
            'type' => 'integer',
            'minimum' => 1,
            'maximum' => self::MaxLimit,
            'default' => self::DefaultLimit,
            'description' => 'Maximum number of elements to return.',
        ],
    ];

    /**
     * Returns JSON Schema properties for the params an element type's query declares with {@see ElementQueryParam} that
     * apply to the element type.
     *
     * @param  class-string<ElementInterface>  $elementType
     * @param  list<string>|null  $only  Param names to return, whether or not they apply to the element type. Every name must be declared.
     * @return array<string, array<string, mixed>>
     */
    public static function properties(string $elementType, ?array $only = null): array
    {
        if ($only === null) {
            $params = ElementQueryParam::forElementType($elementType);
        } else {
            $queryClass = $elementType::find()::class;
            $params = ElementQueryParam::all($queryClass);
            $missing = array_diff($only, array_keys($params));

            if ($missing !== []) {
                throw new LogicException("[$queryClass] does not declare the query params: ".implode(', ', $missing).'.');
            }

            $params = Arr::only($params, $only);
        }

        return collect($params)->map(fn (ElementQueryParam $param, string $name): array => [
            ...ElementSchemaTypes::queryParam($param->type),
            'description' => trim($param->descriptionFor($elementType).' '.$param->companion(Mcp::class)?->hint),
        ])->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $properties
     * @return list<array{name: string, type: string, description: string}>
     */
    public function describe(array $properties): array
    {
        return collect($properties)
            ->map(fn (array $schema, string $name): array => [
                'name' => $name,
                'type' => $this->type($schema),
                'description' => $schema['description'] ?? '',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $criteria
     * @return array<string, mixed> Normalized criteria applied to the query.
     */
    public function apply(ElementQueryInterface $query, array $criteria): array
    {
        $criteria['limit'] = $this->limit($criteria['limit'] ?? self::DefaultLimit);
        $criteria['offset'] = $this->offset($criteria['offset'] ?? 0);

        Typecast::configure($query, $criteria);

        return $criteria;
    }

    /**
     * Reads a page and determines continuation before callers filter records by permission.
     *
     * @param  array<string, mixed>  $criteria
     * @return array{elements: array<ElementInterface|array<string, mixed>>, limit: int, offset: int, nextOffset: int|null}
     */
    public function page(ElementQueryInterface $query, array $criteria): array
    {
        $criteria = $this->apply($query, $criteria);
        $limit = $criteria['limit'];
        $offset = $criteria['offset'];
        $elements = $query->limit($limit + 1)->all();

        return [
            'elements' => array_slice($elements, 0, $limit),
            'limit' => $limit,
            'offset' => $offset,
            'nextOffset' => count($elements) > $limit ? $offset + $limit : null,
        ];
    }

    private function limit(mixed $limit): int
    {
        if (! is_int($limit)) {
            throw new ToolCallException('Element query criteria.limit must be an integer.');
        }

        return max(1, min($limit, self::MaxLimit));
    }

    private function offset(mixed $offset): int
    {
        if (! is_int($offset)) {
            throw new ToolCallException('Element query criteria.offset must be an integer.');
        }

        return max(0, $offset);
    }

    /** @param array<string, mixed> $schema */
    private function type(array $schema): string
    {
        if (isset($schema['anyOf']) && is_array($schema['anyOf'])) {
            return collect($schema['anyOf'])
                ->flatMap(fn (array $option): array => explode('|', $this->type($option)))
                ->unique()
                ->implode('|');
        }

        if (isset($schema['enum']) && is_array($schema['enum'])) {
            return 'string';
        }

        $type = $schema['type'] ?? 'mixed';

        if (is_array($type)) {
            return implode('|', $type);
        }

        if ($type === 'array' && isset($schema['items']) && is_array($schema['items'])) {
            $itemType = $this->type($schema['items']);

            return (str_contains($itemType, '|') ? "($itemType)" : $itemType).'[]';
        }

        return is_string($type) ? $type : 'mixed';
    }
}
