<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown;

readonly class MarkdownOptions
{
    public function __construct(
        public ?string $flavor = null,
        public bool $inlineOnly = false,
        public bool $allowUnsafeLinks = false,
    ) {}

    public function cacheKey(): string
    {
        return implode(':', [
            $this->resolvedFlavor(),
            $this->inlineOnly ? 'inline' : 'block',
            $this->allowUnsafeLinks ? 'unsafe' : 'safe',
        ]);
    }

    public function resolvedFlavor(): string
    {
        return $this->flavor ?? 'original';
    }
}
