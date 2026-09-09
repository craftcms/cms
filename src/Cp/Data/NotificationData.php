<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\Data;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/** @implements Arrayable<string, mixed> */
readonly class NotificationData implements Arrayable, JsonSerializable
{
    /** @param list<NotificationButtonData> $buttons */
    public function __construct(
        public string $id,
        public string $kind,
        public ?string $title,
        public string $messageHtml,
        public ?string $byline,
        public ?string $icon,
        public ?string $image,
        public ?string $imageAlt,
        public ?string $url,
        public array $buttons,
        public string $createdAt,
        public string $createdAtLabel,
        public bool $unread,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            ...get_object_vars($this),
            'buttons' => collect($this->buttons)->toArray(),
        ];
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
