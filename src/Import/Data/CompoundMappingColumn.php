<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;

/**
 * A destination that maps through several subfield columns under one heading, e.g. a location's latitude and longitude.
 *
 * @since 6.0.0
 */
readonly class CompoundMappingColumn implements JsonSerializable
{
    /**
     * @param  list<MappingColumn>  $subfields
     */
    public function __construct(
        public ?string $heading,
        public array $subfields,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'heading' => $this->heading,
            'subfields' => array_map(fn (MappingColumn $col): array => $col->jsonSerialize(), $this->subfields),
        ];
    }
}
