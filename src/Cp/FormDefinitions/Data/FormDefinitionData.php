<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Data;

use JsonSerializable;

readonly class FormDefinitionData implements JsonSerializable
{
    /** @param list<FormElementData> $elements */
    public function __construct(
        public array $elements,
    ) {}

    /** @return array{elements: list<array<string, mixed>>} */
    public function jsonSerialize(): array
    {
        return [
            'elements' => array_map(
                fn (FormElementData $element): array => $element->jsonSerialize(),
                $this->elements,
            ),
        ];
    }
}
