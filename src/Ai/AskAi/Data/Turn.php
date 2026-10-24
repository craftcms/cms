<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi\Data;

use CraftCms\Cms\Ai\AskAi\Answer;

/**
 * A turn of an “Ask AI” conversation, for the panel to send back with follow-ups. The server doesn’t keep the
 * conversation.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class Turn
{
    /**
     * @param  string  $prompt  The prompt, with its field references resolved.
     * @param  string  $answer  The model’s answer, as {@see Answer::toJson()} encodes it.
     */
    public function __construct(
        public string $prompt,
        public string $answer,
    ) {}
}
