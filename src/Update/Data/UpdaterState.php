<?php

declare(strict_types=1);

namespace CraftCms\Cms\Update\Data;

use CraftCms\Cms\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;
use Spatie\TypeScriptTransformer\Attributes\Optional;

/**
 * @internal
 *
 * @implements Arrayable<string, mixed>
 */
class UpdaterState implements Arrayable
{
    public function __construct(
        public string $data,
        public string $finishUrl,
        #[Optional]
        public ?string $status = null,
        #[Optional]
        public ?string $error = null,
        #[Optional]
        public ?string $errorDetails = null,
        #[Optional]
        public ?string $nextUrl = null,
        /** @var UpdaterOption[]|null */
        #[Optional]
        public ?array $options = null,
        #[Optional]
        public ?bool $finished = null,
        #[Optional]
        public ?string $returnUrl = null,
    ) {}

    /** @param array<string, mixed> $state */
    public static function fromArray(array $state): self
    {
        $state['options'] = isset($state['options']) ? array_map(UpdaterOption::fromArray(...), $state['options']) : null;

        return new self(...$state);
    }

    #[\Override]
    public function toArray(): array
    {
        $state = get_object_vars($this);
        $state['options'] = $this->options === null ? null : array_map(fn (UpdaterOption $option) => $option->toArray(), $this->options);

        return Arr::whereNotNull($state);
    }
}
