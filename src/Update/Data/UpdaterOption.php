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
class UpdaterOption implements Arrayable
{
    public function __construct(
        public string $label,
        #[Optional]
        public ?string $url = null,
        #[Optional]
        public ?string $email = null,
        #[Optional]
        public ?string $subject = null,
        #[Optional]
        public ?bool $submit = null,
        #[Optional]
        public ?string $nextUrl = null,
        #[Optional]
        public ?string $status = null,
        #[Optional]
        public ?string $data = null,
        #[Optional]
        public ?bool $finished = null,
        #[Optional]
        public ?string $returnUrl = null,
        #[Optional]
        public ?string $error = null,
        #[Optional]
        public ?string $errorDetails = null,
        /** @var UpdaterOption[]|null */
        #[Optional]
        public ?array $options = null,
    ) {}

    /** @param array<string, mixed> $state */
    public static function fromArray(array $state): self
    {
        $state['options'] = isset($state['options']) ? array_map(self::fromArray(...), $state['options']) : null;

        return new self(...$state);
    }

    #[\Override]
    public function toArray(): array
    {
        $state = get_object_vars($this);
        $state['options'] = $this->options === null ? null : array_map(fn (self $option) => $option->toArray(), $this->options);

        return Arr::whereNotNull($state);
    }
}
