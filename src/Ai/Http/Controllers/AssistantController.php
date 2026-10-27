<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Http\Controllers;

use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Assistant\AssistantStreamProtocol;
use CraftCms\Cms\Ai\Assistant\Content\ElementContexts;
use CraftCms\Cms\Ai\Assistant\Content\ElementContextType;
use CraftCms\Cms\Ai\Assistant\McpTools;
use CraftCms\Cms\Ai\Assistant\MessageContext;
use CraftCms\Cms\Ai\Assistant\Transcript;
use CraftCms\Cms\Ai\AssistantContextTypes;
use CraftCms\Cms\Ai\Enums\Capability;
use CraftCms\Cms\Ai\Exceptions\AiUnavailableException;
use CraftCms\Cms\Ai\ModelImages;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Ai\Tasks\GenerateTitle;
use CraftCms\Cms\Asset\Elements\Asset;
use CraftCms\Cms\Asset\Enums\FileKind;
use CraftCms\Cms\Cms;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Element\Contracts\ElementInterface;
use CraftCms\Cms\Element\Queries\Contracts\NestedElementQueryInterface;
use CraftCms\Cms\Mcp\Elements\ElementAdapterRegistry;
use CraftCms\Cms\Support\Facades\Ai;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use CraftCms\Cms\Ui\Controls\ElementSelect;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\AgentUserInteraction\AgentUserInteraction;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Laravel\Ai\Exceptions\StreamErrorException;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Mcp\Exception\ToolCallException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

use function CraftCms\Cms\t;

/**
 * The AI assistant’s chat, which speaks the AG-UI protocol that TanStack AI’s client uses.
 *
 * Conversations are stored on the server, keyed by the thread IDs the client chooses, so the client only sends its
 * new message, or its answers to approval requests. Each request comes with the items the user added to the context, in
 * `forwardedProps.context`, as `{type, ref, ...}`, which their {@see AssistantContextTypes} resolve.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class AssistantController
{
    /**
     * The most suggestions an `@` in the message box offers.
     */
    private const int MENTION_LIMIT = 10;

    public function __construct(
        private ConversationStore $conversations,
    ) {}

    /**
     * Runs a turn of a conversation, and streams it as AG-UI events.
     */
    public function run(
        Request $request,
        McpTools $tools,
        AssistantContextTypes $contextTypes,
        ModelImages $modelImages,
    ): Response {
        $request->validate([
            'threadId' => ['required', 'uuid'],
            'forwardedProps.context' => ['array'],
        ]);

        $chat = AgentUserInteraction::chat($request);
        $threadId = $chat->threadId();
        $user = $this->user($request);

        abort_if($chat->message() === null && $chat->decisions() === null, 400, 'Send a message, or answers to approval requests.');

        // Before the conversation is stored, so a message that can’t be answered doesn’t leave one in the history.
        try {
            Ai::resolve(Assistant::handle());
        } catch (AiUnavailableException $exception) {
            return new JsonResponse(['message' => $exception->getMessage()], 422);
        }

        $firstMessage = null;

        if (! $this->exists($threadId)) {
            $message = $chat->message() ?? abort(400, 'A new conversation must start with a message.');
            $firstMessage = MessageContext::strip($message->content ?? '');
            // Replaced with a generated title once the first reply is done.
            $this->conversations->storeConversation(
                Conversation::participantType($user),
                Conversation::participantKey($user),
                Str::limit($firstMessage, 100),
                $threadId,
            );
        }

        abort_unless($this->belongsTo($threadId, $user), 404);

        // What earlier messages were sent with comes after this message’s, so the model can keep working with it.
        $context = $request->array('forwardedProps.context');
        $refs = array_column(array_filter($context, is_array(...)), 'ref');
        $earlier = array_filter(
            MessageContext::earlierItems($threadId),
            static fn (array $item): bool => ! in_array($item['ref'], $refs, true),
        );

        // The image of the asset open in the editor goes with the first message it’s open for, and stays in the
        // conversation for later ones.
        $image = $this->openImage($context, $user);
        $imageSent = $image !== null && $this->hasImage($threadId, $image);
        $attachImage = $image !== null && ! $imageSent && $chat->message() !== null;

        $agent = new AssistantAgent(
            $tools,
            $user,
            $contextTypes->resolve($request, [...$context, ...$earlier]),
            $imageSent || $attachImage ? $image->title : null,
        );

        $response = Ai::task(Assistant::handle())
            ->stream($agent->continue($threadId, as: $user), $chat, $attachImage ? [$modelImages->forAsset($image)] : [])
            ->catch(fn (Throwable $exception) => $this->logProviderError($threadId, $exception));

        if ($firstMessage !== null) {
            $response->then(fn () => $this->nameConversation($threadId, $firstMessage));
        }

        return $response
            ->usingProtocol(new AssistantStreamProtocol($threadId, $chat->runId() ?: null))
            ->toResponse($request);
    }

    /**
     * Returns a conversation’s messages, for the chat to show it again after the page reloads.
     */
    public function show(Request $request, Transcript $transcript): JsonResponse
    {
        $threadId = (string) $request->query('threadId');
        $user = $this->user($request);

        if (! Str::isUuid($threadId) || ! $this->exists($threadId)) {
            return new JsonResponse(['messages' => [], 'activeRun' => null, 'interrupts' => null]);
        }

        abort_unless($this->belongsTo($threadId, $user), 404);

        return new JsonResponse([...$transcript->load($threadId), 'activeRun' => null]);
    }

    /**
     * Returns the elements the user can view that match what they typed after `@` in the message box, as the message
     * box’s suggestions.
     */
    public function mentions(Request $request, ElementAdapterRegistry $adapters): JsonResponse
    {
        $search = trim(Str::after($request->string('query')->value(), '@'));
        $limit = min(max($request->integer('limit', self::MENTION_LIMIT), 1), self::MENTION_LIMIT);
        $actor = $request->craftUser() ?? abort(401);
        $options = [];

        foreach ($adapters->handles() as $handle) {
            $adapter = $adapters->find($handle);

            try {
                $query = $adapter?->listQuery($actor)->limit($limit);
            } catch (ToolCallException) {
                // The user can’t view any elements of the type.
                continue;
            }

            if ($query === null) {
                continue;
            }

            if ($search !== '') {
                $query->search($search);
            }

            // Nested elements, such as Matrix blocks, are mentioned through what they’re in.
            if ($query instanceof NestedElementQueryInterface) {
                $query->primaryOwnerId(':empty:');
            }

            foreach ($query->all() as $element) {
                if (! $element instanceof ElementInterface || ! $adapter->canView($actor, $element)) {
                    continue;
                }

                $options[] = [
                    'label' => $element->getUiLabel(),
                    // Keeps the `@`, so the message shows what’s a mention.
                    'value' => '@'.$element->getUiLabel(),
                    'data' => [
                        'elementType' => $element::class,
                        'elementId' => $element->id,
                        'siteId' => $element->siteId,
                        // What kind of element it is, which the suggestion shows beside it.
                        'hint' => $element::displayName(),
                        'status' => ElementSelect::presentation($element)['status'],
                    ],
                ];
            }
        }

        return new JsonResponse(array_slice($options, 0, $limit));
    }

    /**
     * Renames one of the user’s conversations.
     */
    public function rename(Request $request): JsonResponse
    {
        $request->validate([
            'threadId' => ['required', 'uuid'],
            'title' => ['required', 'string', 'max:255'],
        ]);

        $threadId = $this->ownThread($request);
        $title = trim($request->string('title')->value());

        Conversation::query()->whereKey($threadId)->update(['title' => $title]);

        return new JsonResponse(['title' => $title]);
    }

    /**
     * Deletes one of the user’s conversations, with its messages.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['threadId' => ['required', 'uuid']]);

        $threadId = $this->ownThread($request);

        DB::transaction(static function () use ($threadId): void {
            DB::table(Table::AGENT_CONVERSATION_MESSAGES)->where('conversation_id', $threadId)->delete();
            DB::table(Table::AGENT_CONVERSATIONS)->where('id', $threadId)->delete();
        });

        return new JsonResponse(['deleted' => true]);
    }

    /**
     * Returns the fields of an element that can be added to a message’s context. The element is identified as
     * {@see ElementContexts::resolve()} describes, without `fields`.
     */
    public function contextFields(Request $request, ElementContexts $elementContexts): JsonResponse
    {
        $context = $elementContexts->resolve($request, [[...$request->all(), 'fields' => null]])[0] ?? abort(404);

        return new JsonResponse([
            'fields' => $elementContexts->fields($context->element),
            // For the element’s chip, as element chips show it elsewhere.
            'status' => ElementSelect::presentation($context->element)['status'],
        ]);
    }

    /**
     * Returns the user’s conversations, latest first, for the chat history, grouped by the day of their latest message
     * in the user’s time zone: “Today”, “Yesterday”, and then each earlier date.
     */
    public function conversations(Request $request): JsonResponse
    {
        $user = $this->user($request);
        $formatter = I18N::getFormatter();

        $conversations = Conversation::query()
            ->tap(AssistantAgent::onlyItsConversations(...))
            ->where('participant_type', Conversation::participantType($user))
            ->where('participant_id', Conversation::participantKey($user))
            ->latest('updated_at')
            ->get(['id', 'title', 'updated_at']);

        $groups = [];

        foreach ($conversations as $conversation) {
            $item = $this->historyItem($conversation);
            $date = Date::parse($item['updatedAt'])->setTimezone(Cms::timezone());
            $heading = match (true) {
                $date->isToday() => t('Today'),
                $date->isYesterday() => t('Yesterday'),
                default => $formatter->asDate($date, Locale::LENGTH_LONG),
            };

            $groups[$heading] ??= ['heading' => $heading, 'conversations' => []];
            $groups[$heading]['conversations'][] = $item;
        }

        return new JsonResponse(['groups' => array_values($groups)]);
    }

    /**
     * @return array{id: string, title: string, updatedAt: string}
     */
    private function historyItem(Conversation $conversation): array
    {
        // Serialized by the model, so the date is in ISO 8601 with its time zone.
        $data = $conversation->toArray();

        return ['id' => $data['id'], 'title' => $data['title'], 'updatedAt' => $data['updated_at']];
    }

    /**
     * Logs a reply that failed with an error from the provider, such as a rate limit. The stream protocol reports other
     * exceptions itself, but leaves these out, as it sends their message to the chat, where it’s otherwise lost.
     */
    private function logProviderError(string $threadId, Throwable $exception): void
    {
        if (! $exception instanceof StreamErrorException) {
            return;
        }

        Log::error("The AI assistant couldn’t reply: {$exception->getMessage()}", [
            'threadId' => $threadId,
            'type' => $exception->error?->type,
            'recoverable' => $exception->error?->recoverable,
            'metadata' => $exception->error?->metadata,
        ]);
    }

    /**
     * Replaces a conversation’s title, the start of its first message, with one generated from the message. A title
     * the user gave it in the meantime stays, as does the one it has if no title can be generated.
     */
    private function nameConversation(string $threadId, string $message): void
    {
        $title = GenerateTitle::generate($message);

        if ($title !== null) {
            Conversation::query()
                ->whereKey($threadId)
                ->where('title', Str::limit($message, 100))
                ->update(['title' => $title]);
        }
    }

    private function user(Request $request): User
    {
        return $request->craftUser()?->asElement() ?? abort(401);
    }

    /**
     * Returns the conversation a request is for, if it’s the user’s.
     */
    private function ownThread(Request $request): string
    {
        $threadId = $request->string('threadId')->value();

        abort_unless($this->exists($threadId) && $this->belongsTo($threadId, $this->user($request)), 404);

        return $threadId;
    }

    private function exists(string $threadId): bool
    {
        return Conversation::query()->whereKey($threadId)->exists();
    }

    private function belongsTo(string $threadId, User $user): bool
    {
        assert($this->conversations instanceof VerifiesConversationOwnership);

        return $this->conversations->conversationBelongsTo(
            $threadId,
            Conversation::participantType($user),
            Conversation::participantKey($user),
        );
    }

    /**
     * Returns the image asset open in the user’s editor, if they can view it, and the assistant’s model can see images.
     *
     * @param  array<mixed>  $context
     */
    private function openImage(array $context, User $user): ?Asset
    {
        $item = array_find($context, static fn (mixed $item): bool => is_array($item)
            && ($item['type'] ?? null) === ElementContextType::handle()
            && ($item['open'] ?? false) === true
            && ($item['elementType'] ?? null) === Asset::class
            && is_int($item['elementId'] ?? null));

        if ($item === null || ! Ai::supports(Assistant::handle(), Capability::Vision)) {
            return null;
        }

        $asset = Asset::find()
            ->id($item['elementId'])
            ->siteId(is_int($item['siteId'] ?? null) ? $item['siteId'] : null)
            ->status(null)
            ->one();

        return $asset instanceof Asset && $asset->kind === FileKind::Image->value && $user->can('view', $asset) ? $asset : null;
    }

    /**
     * Returns whether a message in a conversation was sent with an asset’s image, as its file is now.
     */
    private function hasImage(string $conversationId, Asset $asset): bool
    {
        return ConversationMessage::query()
            ->where('conversation_id', $conversationId)
            ->where('role', 'user')
            ->pluck('attachments')
            ->contains(static fn (mixed $attachments): bool => array_any(
                is_array($attachments) ? $attachments : [],
                static fn (mixed $attachment): bool => is_array($attachment) && ($attachment['name'] ?? null) === ModelImages::name($asset),
            ));
    }
}
