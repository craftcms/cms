<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\Optional;

/**
 * A setting a field offers when it’s mapped in an import, e.g. what to do with a conflicting file.
 *
 * @since 6.0.0
 */
readonly class FieldMappingSetting implements JsonSerializable
{
    /**
     * @param  list<array{value: string|int, label: string}>  $options
     */
    public function __construct(
        public string $name,
        public string $label,
        public array $options,
        public string $default,
        #[Optional]
        public ?string $instructions = null,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        $payload = [
            'name' => $this->name,
            'label' => $this->label,
            'options' => $this->options,
            'default' => $this->default,
        ];

        if ($this->instructions !== null) {
            $payload['instructions'] = $this->instructions;
        }

        return $payload;
    }
}
