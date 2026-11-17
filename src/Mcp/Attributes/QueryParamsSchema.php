<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Attributes;

use Attribute;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Attributes\ElementQueryParam;
use CraftCms\Cms\Mcp\ElementQueryCriteria;
use Mcp\Capability\Attribute\Schema;

/**
 * Defines a tool parameter's object schema from element query params declared with
 * {@see ElementQueryParam}.
 *
 * @since 6.0.0
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class QueryParamsSchema extends Schema
{
    /**
     * @param  array<class-string<ElementInterface>, list<string>>  $params  Query param names to accept, per element type.
     * @param  array<string, array<string, mixed>>  $properties  Additional property schemas, which override query params.
     */
    public function __construct(array $params, array $properties = [])
    {
        $schemas = [];

        foreach ($params as $elementType => $names) {
            $schemas = [...$schemas, ...ElementQueryCriteria::properties($elementType, $names)];
        }

        parent::__construct(definition: [
            'type' => 'object',
            'properties' => [...$schemas, ...$properties],
            'additionalProperties' => false,
        ]);
    }
}
