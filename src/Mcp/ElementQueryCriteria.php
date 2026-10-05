<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Element\Queries\Contracts\ElementQueryInterface;
use CraftCms\Cms\Support\Typecast;
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

    public const array SchemaProperties = [
        'id' => [
            ...self::IntegerOrIntegersSchema,
            'description' => 'Element ID or IDs.',
        ],
        'uid' => [
            ...self::StringOrStringsSchema,
            'description' => 'Element UID or UIDs.',
        ],
        'status' => [
            'anyOf' => [
                ['type' => 'string'],
                ['type' => 'array', 'items' => ['type' => 'string']],
                ['type' => 'null'],
            ],
            'description' => 'Element status criteria. Use null to include all statuses.',
        ],
        'search' => [
            'type' => 'string',
            'description' => 'Native Craft element search query.',
        ],
        'archived' => [
            'type' => 'boolean',
            'description' => 'Whether archived elements may be returned.',
        ],
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
}
