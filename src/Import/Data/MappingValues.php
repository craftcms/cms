<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * The parallel trees an import step's mapping screen edits, all keyed alike.
 *
 * @since 6.0.0
 */
readonly class MappingValues implements JsonSerializable
{
    /**
     * @param  array<array-key, mixed>  $map
     * @param  array<array-key, mixed>  $matchCriteria
     * @param  array<array-key, mixed>  $clearableItems
     * @param  array<array-key, mixed>  $keepMissingNestedElements
     * @param  array<array-key, mixed>  $fieldSettings
     */
    public function __construct(
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $map = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $matchCriteria = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $clearableItems = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $keepMissingNestedElements = [],
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $fieldSettings = [],
    ) {}

    /** @return array<string, array<array-key, mixed>> */
    public function jsonSerialize(): array
    {
        return [
            'map' => $this->map,
            'matchCriteria' => $this->matchCriteria,
            'clearableItems' => $this->clearableItems,
            'keepMissingNestedElements' => $this->keepMissingNestedElements,
            'fieldSettings' => $this->fieldSettings,
        ];
    }
}
