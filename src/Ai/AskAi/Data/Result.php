<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi\Data;

/**
 * What the model returned: the values it changed, and what it has to say about the content.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class Result
{
    /**
     * @param  list<Proposal>  $proposals
     * @param  string|null  $summary  What the model changed, or its review when it changed nothing.
     * @param  list<Finding>  $findings
     */
    public function __construct(
        public array $proposals,
        public ?string $summary,
        public array $findings,
        public Turn $turn,
    ) {}
}
