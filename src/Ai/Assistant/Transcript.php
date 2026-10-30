<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use Carbon\CarbonInterface;
use CraftCms\Cms\Support\Json;
use Laravel\Ai\AgentUserInteraction\AgentUserInteraction;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Models\ConversationMessage;

/**
 * Turns a stored conversation into the messages the chat shows, in TanStack AI’s `UIMessage` shape, so a conversation
 * can be shown again after the page reloads.
 *
 * @internal
 *
 * @since 6.0.0
 */
class Transcript
{
    /**
     * Returns a conversation’s messages, and the tool calls it’s waiting for the user to approve, as AG-UI interrupts of
     * the run that paused for them, or `null` when there are none. Only the latest message can be waiting: one paused
     * before the user sent another message is over.
     *
     * @return array{
     *     messages: list<array{id: string, role: string, parts: list<array<string, mixed>>, createdAt: ?string}>,
     *     interrupts: array{runId: string, pending: list<array<string, mixed>>}|null,
     * }
     */
    public function load(string $conversationId): array
    {
        $rows = ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->oldest()
            ->orderBy('id')
            ->get();

        $latest = $rows->last();
        $paused = $latest?->role === 'assistant' && $latest->status === MessageStatus::Paused ? $latest : null;
        $pending = $paused ? AgentUserInteraction::toInterrupts([$paused]) : [];

        return [
            'messages' => $rows
                // Prompts the user didn’t write, which only the model gets.
                ->reject(static fn (ConversationMessage $message): bool => (bool) ($message->meta[Crafty::QUIET] ?? false))
                ->map(function (ConversationMessage $message) use ($paused): array {
                    $createdAt = $message->getAttribute($message->getCreatedAtColumn());

                    return [
                        'id' => $message->id,
                        'role' => $message->role,
                        'parts' => $message->role === 'user'
                            ? [['type' => 'text', 'content' => (string) $message->content]]
                            : $this->assistantParts($message, $message === $paused),
                        'createdAt' => $createdAt instanceof CarbonInterface ? $createdAt->toIso8601String() : null,
                    ];
                })
                ->values()
                ->all(),
            'interrupts' => $paused && $pending !== [] ? ['runId' => $paused->id, 'pending' => $pending] : null,
        ];
    }

    /**
     * A tool call’s result as the chat shows it, with the results of the assistant’s display tools expanded, as
     * {@see AssistantStreamProtocol} streams them.
     *
     * @param  array<string, mixed>  $call
     */
    private function output(array $call): mixed
    {
        return is_string($call['name'] ?? null) && is_string($call['result']) && ! ($call['denied'] ?? false)
            ? ToolDisplays::forDisplay($call['name'], $call['result'])
            : $call['result'];
    }

    /**
     * Each step’s text, then its tool calls, in the order the model wrote them.
     *
     * @return list<array<string, mixed>>
     */
    private function assistantParts(ConversationMessage $message, bool $isPaused): array
    {
        $parts = [];

        foreach ($message->steps ?? [] as $step) {
            if (($step['content'] ?? '') !== '') {
                $parts[] = ['type' => 'text', 'content' => $step['content']];
            }

            foreach ($step['tool_calls'] ?? [] as $call) {
                $isPending = $isPaused && PendingApproval::isPending($call);

                $parts[] = [
                    'type' => 'tool-call',
                    'id' => (string) $call['id'],
                    'name' => (string) $call['name'],
                    'arguments' => Json::encode((object) ($call['arguments'] ?? [])),
                    // Calls without a result that aren’t waiting for the user never finished: the turn failed, or the user moved on.
                    'state' => match (true) {
                        $isPending => 'approval-requested',
                        array_key_exists('result', $call) => 'complete',
                        default => 'error',
                    },
                    ...array_key_exists('result', $call) ? ['output' => $this->output($call)] : [],
                    // Calls that needed approval, whether the user has answered yet, and how.
                    ...array_key_exists('approval_reason', $call) || ($call['denied'] ?? false) ? ['approval' => [
                        'id' => (string) $call['id'],
                        'needsApproval' => true,
                        ...$isPending ? [] : ['approved' => ! ($call['denied'] ?? false)],
                    ]] : [],
                ];
            }
        }

        if ($parts === [] && (string) $message->content !== '') {
            $parts[] = ['type' => 'text', 'content' => (string) $message->content];
        }

        return $parts;
    }
}
