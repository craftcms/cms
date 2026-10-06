<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp;

use CraftCms\Cms\Element\Contracts\ElementInterface;
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
        'site' => [
            ...self::StringOrStringsSchema,
            'description' => 'Site handle or handles.',
        ],
        'siteId' => [
            ...self::IntegerOrIntegersSchema,
            'description' => 'Site ID or IDs.',
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
        'trashed' => [
            'type' => ['boolean', 'null'],
            'description' => 'true returns deleted elements, false returns active elements, and null includes both. Use status: null when listing deleted elements.',
        ],
        'relatedTo' => [
            'type' => 'array',
            'description' => 'Native Craft relation criteria, such as related element IDs.',
        ],
        'with' => [
            'type' => 'array',
            'items' => ['type' => 'string'],
            'description' => 'Eager-loading handles or paths.',
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

    public const array EntrySchemaProperties = [
        'slug' => [...self::StringOrStringsSchema, 'description' => 'Entry slug criteria.'],
        'section' => [...self::StringOrStringsSchema, 'description' => 'Section handle or handles.'],
        'sectionId' => [...self::IntegerOrIntegersSchema, 'description' => 'Section ID or IDs.'],
        'type' => [...self::StringOrStringsSchema, 'description' => 'Entry type handle or handles.'],
        'typeId' => [...self::IntegerOrIntegersSchema, 'description' => 'Entry type ID or IDs.'],
        'authorId' => [...self::IntegerOrIntegersSchema, 'description' => 'Author user ID or IDs.'],
    ];

    public const array AssetSchemaProperties = [
        'volume' => [...self::StringOrStringsSchema, 'description' => 'Asset volume handle or handles.'],
        'volumeId' => [...self::IntegerOrIntegersSchema, 'description' => 'Asset volume ID or IDs.'],
        'folderId' => [...self::IntegerOrIntegersSchema, 'description' => 'Asset folder ID or IDs.'],
        'filename' => [...self::StringOrStringsSchema, 'description' => 'Asset filename criteria.'],
        'kind' => [...self::StringOrStringsSchema, 'description' => 'Asset file-kind criteria.'],
    ];

    public const array UserSchemaProperties = [
        'group' => [...self::StringOrStringsSchema, 'description' => 'User group handle or handles.'],
        'groupId' => [...self::IntegerOrIntegersSchema, 'description' => 'User group ID or IDs.'],
        'username' => [...self::StringOrStringsSchema, 'description' => 'Username criteria.'],
        'email' => [...self::StringOrStringsSchema, 'description' => 'User email criteria.'],
        'firstName' => ['type' => 'string', 'description' => 'First name criteria.'],
        'lastName' => ['type' => 'string', 'description' => 'Last name criteria.'],
        'fullName' => ['type' => 'string', 'description' => 'Full name criteria.'],
        'admin' => ['type' => 'boolean', 'description' => 'Whether to return admin users.'],
        'hasPhoto' => ['type' => 'boolean', 'description' => 'Whether to return users with a photo.'],
        'lastLoginDate' => ['type' => 'string', 'description' => 'Last login date criteria.'],
    ];

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
                ->map(fn (array $option): string => $this->type($option))
                ->implode('|');
        }

        $type = $schema['type'] ?? 'mixed';

        if (is_array($type)) {
            return implode('|', $type);
        }

        if ($type === 'array' && isset($schema['items']) && is_array($schema['items'])) {
            return $this->type($schema['items']).'[]';
        }

        return is_string($type) ? $type : 'mixed';
    }
}
