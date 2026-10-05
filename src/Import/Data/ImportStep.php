<?php

declare(strict_types=1);

namespace CraftCms\Cms\Import\Data;

use JsonSerializable;
use Spatie\TypeScriptTransformer\Attributes\LiteralTypeScriptType;

/**
 * One import step, as the edit screen holds it and as it's posted back to the server.
 *
 * @since 6.0.0
 */
readonly class ImportStep implements JsonSerializable
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function __construct(
        public ?string $uid,
        public ?string $type,
        public ?string $source,
        public ?string $transformer,
        public ?int $batchSize,
        #[LiteralTypeScriptType('Record<string, unknown>')]
        public array $settings,
    ) {}

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return [
            'uid' => $this->uid,
            'type' => $this->type,
            'source' => $this->source,
            'transformer' => $this->transformer,
            'batchSize' => $this->batchSize,
            'settings' => $this->settings,
        ];
    }
}
