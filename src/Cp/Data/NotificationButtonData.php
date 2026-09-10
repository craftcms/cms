<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use CraftCms\Cms\Cp\Enums\ButtonVariant;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/** @implements Arrayable<string, string|null> */
readonly class NotificationButtonData implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $label,
        public string $url,
        public ?string $target = null,
        public ?string $icon = null,
        public ButtonVariant $variant = ButtonVariant::Solid,
    ) {}

    /** @return array{label: string, url: string, target: string|null, icon: string|null, variant: string} */
    public function toArray(): array
    {
        return [
            ...get_object_vars($this),
            'variant' => $this->variant->value,
        ];
    }

    /** @return array{label: string, url: string, target: string|null, icon: string|null, variant: string} */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
