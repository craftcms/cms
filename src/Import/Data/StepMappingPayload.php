<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Ui\Controls\Concerns\HasTextExpander;
use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;
use Spatie\TypeScriptTransformer\Attributes\Optional;

/**
 * The response to a request for a draft import step's mapping structure: either the columns to map,
 * or why the step can't be mapped yet (and which step attribute that's about, if any).
 *
 * @phpstan-import-type TextExpanderTrigger from HasTextExpander
 *
 * @since 6.0.0
 */
readonly class StepMappingPayload implements JsonSerializable
{
    /**
     * @param  list<MappingColumn|CompoundMappingColumn>|null  $destinationCols
     * @param  list<SourceColumn>|null  $sourceDataCols
     * @param  array<array-key, mixed>|null  $suggestions
     * @param  list<TextExpanderTrigger>|null  $templateTextExpanderTriggers
     */
    public function __construct(
        public bool $available,
        #[Optional]
        public ?string $message = null,
        #[Optional]
        public ?string $attribute = null,
        #[Optional]
        public ?array $destinationCols = null,
        #[Optional]
        public ?array $sourceDataCols = null,
        #[Optional]
        public ?MappingValues $values = null,
        #[Optional]
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public ?array $suggestions = null,
        #[Optional]
        #[LiteralTypeScriptType('unknown[]')]
        public ?array $templateTextExpanderTriggers = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return Arr::whereNotNull([
            'available' => $this->available,
            'message' => $this->message,
            'attribute' => $this->attribute,
            'destinationCols' => $this->destinationCols === null ? null : array_map(
                fn (MappingColumn|CompoundMappingColumn $col): array => $col->jsonSerialize(),
                $this->destinationCols,
            ),
            'sourceDataCols' => $this->sourceDataCols === null ? null : array_map(
                fn (SourceColumn $col): array => $col->jsonSerialize(),
                $this->sourceDataCols,
            ),
            'values' => $this->values?->jsonSerialize(),
            'suggestions' => $this->suggestions,
            'templateTextExpanderTriggers' => $this->templateTextExpanderTriggers,
        ]);
    }
}
