<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Data;

use CraftCms\Cms\Ai\Contracts\AssistantContextTypeInterface;
use Laravel\Ai\Contracts\Tool;

/**
 * What the AI assistant’s model gets for the items of a context type, as {@see AssistantContextTypeInterface::resolve()}
 * returns it.
 *
 * @since 6.0.0
 */
readonly class AssistantContext
{
    /**
     * @param  string  $description  Describes the items to the model, in its instructions. Markdown.
     * @param  list<Tool>  $tools  Tools that work with the items, offered while they’re in the context.
     */
    public function __construct(
        public string $description,
        public array $tools = [],
    ) {}
}
