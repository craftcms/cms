<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

/**
 * Writes a summary of what it changes, then proposes new values for the fields an editor may change, with findings,
 * through the {@see ProposeChanges} tool.
 *
 * One step is enough: the tool only records the answer, so the model has nothing to continue with after calling it.
 *
 * @internal
 *
 * @since 6.0.0
 */
#[MaxSteps(1)]
class AssistantAgent implements Agent, Conversational, HasTools
{
    use Promptable;

    /**
     * @param  ProposeChanges  $tool  The tool the model submits its answer with.
     * @param  list<Message>  $messages  The conversation’s earlier turns.
     */
    public function __construct(
        private readonly string $instructions,
        private readonly ProposeChanges $tool,
        private readonly array $messages = [],
    ) {}

    public function instructions(): string
    {
        return $this->instructions;
    }

    /**
     * @return list<Message>
     */
    public function messages(): iterable
    {
        return $this->messages;
    }

    /**
     * @return list<Tool>
     */
    public function tools(): iterable
    {
        return [$this->tool];
    }
}
