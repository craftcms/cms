<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;

/**
 * One field layout provider's destination columns, shown under its own heading in a nested mapping panel.
 *
 * @since 6.0.0
 */
readonly class MappingColumnGroup implements JsonSerializable
{
    /**
     * @param  list<MappingColumn|CompoundMappingColumn>  $destinationCols
     */
    public function __construct(
        public ?string $providerName,
        public array $destinationCols,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'providerName' => $this->providerName,
            'destinationCols' => array_map(
                fn (MappingColumn|CompoundMappingColumn $col): array => $col->jsonSerialize(),
                $this->destinationCols,
            ),
        ];
    }
}
