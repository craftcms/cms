<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi\Data;

/**
 * A field that can be chosen as an “Ask AI” prompt’s context.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ContextOption
{
    /**
     * @param  string  $value  The field’s control path, relative to the editor’s scope, joined by dots.
     */
    public function __construct(
        public string $value,
        public string $label,
    ) {}
}
