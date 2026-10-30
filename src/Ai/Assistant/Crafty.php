<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Enums\MessageStatus;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Models\Conversation;

/**
 * Starts a conversation with a reply the assistant didn’t write, so the chat can open with it without a request to
 * the model. The reply follows a prompt that the chat doesn’t show, which the model gets as history, along with the
 * reply, if the user carries on.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class Crafty
{
    /**
     * The meta key of messages the chat doesn’t show.
     */
    public const string QUIET = 'quiet';

    private const string PAYLOAD = 'eyJwcm9tcHQiOiAiSeKAmXZlIHN0YXJ0ZWQgd3JpdGluZyBhIGxldHRlciBpbiBhIGZpZWxkIG9mIHRoZSBlbnRyeSBJIGhhdmUgb3BlbiwgYmVnaW5uaW5nIOKAnERlYXIgU2lyIG9yIE1hZGFtLOKAnS4iLCAicmVwbHkiOiAiSXQgbG9va3MgbGlrZSB5b3XigJlyZSB3cml0aW5nIGEgbGV0dGVyLiBXb3VsZCB5b3UgbGlrZSBoZWxwP1xuXG5gYGB0ZXh0XG4gICBfXyAgfC9cbiAgLyAgXFxcbiAgfCAgfFxuICBAICBAXG4gIHwgIHxcbiAgfHwgfC9cbiAgfHwgfHxcbiAgfFxcXy98XG4gIFxcX19fL1xuYGBgIiwgInRpdGxlIjogIldyaXRpbmcgYSBsZXR0ZXIiLCAicXVlc3Rpb24iOiAiV2hhdCB3b3VsZCB5b3UgbGlrZSB0byBkbz8iLCAib3B0aW9ucyI6IFsiR2V0IGhlbHAgd2l0aCB3cml0aW5nIHRoZSBsZXR0ZXIiLCAiSnVzdCB0eXBlIHRoZSBsZXR0ZXIgd2l0aG91dCBoZWxwIl19';

    public function __construct(
        private ConversationStore $conversations,
    ) {}

    public function start(string $threadId, User $user): void
    {
        /** @var array{prompt: string, reply: string, title: string, question: string, options: list<string>} $payload */
        $payload = Json::decode((string) base64_decode(self::PAYLOAD, true));
        ['prompt' => $prompt, 'reply' => $reply, 'title' => $title] = $payload;
        // Asked as the assistant asks, so the chat shows the answers to choose from.
        $question = [
            'id' => 'call_'.Str::random(24),
            'name' => AskUser::NAME,
            'arguments' => ['question' => $payload['question'], 'options' => $payload['options']],
            'result' => Json::encode([
                'message' => 'Asked the user. Their answer is their next message.',
                'question' => $payload['question'],
                'options' => $payload['options'],
            ]),
        ];
        $type = Conversation::participantType($user);
        $key = Conversation::participantKey($user);

        DB::transaction(function () use ($threadId, $type, $key, $prompt, $reply, $title, $question): void {
            $this->conversations->storeConversation($type, $key, $title, $threadId);
            $promptId = $this->conversations->storeUserMessage($threadId, $type, $key, AssistantAgent::class, new UserMessage($prompt));

            $messages = DB::table(Table::AGENT_CONVERSATION_MESSAGES);
            $messages->clone()->where('id', $promptId)->update(['meta' => Json::encode([self::QUIET => true])]);

            // Stored as the conversation store stores its messages, so it sorts after the prompt.
            $stored = $messages->clone()->where('id', $promptId)->first(['created_at', 'updated_at']);

            $messages->clone()->insert([
                'id' => (string) Str::uuid7(now()->addMillisecond()),
                'conversation_id' => $threadId,
                'participant_type' => $type,
                'participant_id' => $key,
                'agent' => AssistantAgent::class,
                'role' => 'assistant',
                'content' => $reply,
                'attachments' => '[]',
                'steps' => Json::encode([['content' => $reply, 'tool_calls' => [$question]]]),
                'usage' => '[]',
                'meta' => '[]',
                'status' => MessageStatus::Completed->value,
                'created_at' => $stored?->created_at,
                'updated_at' => $stored?->updated_at,
            ]);
        });
    }
}
