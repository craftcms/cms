<?php

declare(strict_types=1);

namespace CraftCms\Cms\Markdown;

/**
 * @since 6.0.0
 */
readonly class MarkdownOptions
{
    public function __construct(
        public ?string $flavor = null,
        public bool $inlineOnly = false,
        public bool $allowUnsafeLinks = false,
        public bool $indentedCode = true,
    ) {}

    public function cacheKey(): string
    {
        return implode(':', [
            $this->resolvedFlavor(),
            $this->inlineOnly ? 'inline' : 'block',
            $this->allowUnsafeLinks ? 'unsafe' : 'safe',
            $this->indentedCode ? 'indented-code' : 'no-indented-code',
        ]);
    }

    public function resolvedFlavor(): string
    {
        return $this->flavor ?? 'original';
    }
}
