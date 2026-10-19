<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

/**
 * A model’s response to an editor’s prompt: the values it changed, and what it has to say about the content.
 *
 * A review can come back with feedback and no values at all.
 *
 * @since 6.0.0
 */
readonly class Answer
{
    /**
     * @param  array<string, mixed>  $values  The values the model changed, indexed by field key.
     * @param  string|null  $summary  What the model changed, or its review when it changed nothing.
     * @param  list<array{field: string|null, quote: string|null, issue: string}>  $findings  Specific problems, each with the key of the field it concerns (or `null` for the whole element), and the passage it’s in, if that passage is in the field.
     */
    public function __construct(
        public array $values,
        public ?string $summary = null,
        public array $findings = [],
    ) {}
}
