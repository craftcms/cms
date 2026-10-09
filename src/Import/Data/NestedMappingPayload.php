<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * The response to a request for a container column's nested mapping structure.
 *
 * @since 6.0.0
 */
readonly class NestedMappingPayload implements JsonSerializable
{
    /**
     * @param  list<MappingColumnGroup>  $groups
     * @param  list<SourceColumn>  $sourceDataCols
     * @param  array<array-key, mixed>  $suggestions
     */
    public function __construct(
        public string $title,
        public ?string $fieldName,
        public array $groups,
        public array $sourceDataCols,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $suggestions,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'title' => $this->title,
            'fieldName' => $this->fieldName,
            'groups' => array_map(fn (MappingColumnGroup $group): array => $group->jsonSerialize(), $this->groups),
            'sourceDataCols' => array_map(fn (SourceColumn $col): array => $col->jsonSerialize(), $this->sourceDataCols),
            'suggestions' => $this->suggestions,
        ];
    }
}
