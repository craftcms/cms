<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi\Data;

use CraftCms\Cms\Ai\AskAi\Target;

/**
 * A problem the model noticed, for a field or for the content as a whole.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class Finding
{
    /**
     * @param  string|null  $key  The key of the field it concerns, or null for the content as a whole.
     * @param  string|null  $path  The field’s {@see ContextOption} value, to ask about the field alone.
     * @param  string|null  $quote  The passage of the field’s current text the problem is in, if any.
     */
    public function __construct(
        public ?string $key,
        public ?string $path,
        public ?string $label,
        public ?string $quote,
        public string $issue,
    ) {}

    /**
     * Returns a finding about the given field, or about the content as a whole.
     */
    public static function about(?Target $target, ?string $quote, string $issue): self
    {
        return new self($target?->id(), $target?->path(), $target?->label(), $quote, $issue);
    }
}
