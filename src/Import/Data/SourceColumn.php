<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\Optional;

/**
 * A column (heading) read from an import step's data source, offered as a mapping option.
 *
 * @since 6.0.0
 */
readonly class SourceColumn implements JsonSerializable
{
    public function __construct(
        public string $label,
        public string $value,
        #[Optional]
        public ?string $hint = null,
    ) {}

    /** @return array<string, string> */
    public function jsonSerialize(): array
    {
        $payload = [
            'label' => $this->label,
            'value' => $this->value,
        ];

        if ($this->hint !== null) {
            $payload['hint'] = $this->hint;
        }

        return $payload;
    }
}
