<?php

declare(strict_types=1);

namespace CraftCms\Cms\View;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class HtmlFragment implements Arrayable, JsonSerializable
{
    public function __construct(
        public string $html = '',
        public string $headHtml = '',
        public string $bodyHtml = '',
    ) {}

    public function isEmpty(): bool
    {
        return $this->html === ''
            && $this->headHtml === ''
            && $this->bodyHtml === '';
    }

    public function toArray(): array
    {
        return [
            'html' => $this->html,
            'headHtml' => $this->headHtml,
            'bodyHtml' => $this->bodyHtml,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
