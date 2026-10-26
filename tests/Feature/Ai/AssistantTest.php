<?php

declare(strict_types=1);

use CraftCms\Cms\Activity\EventTypes\ElementUpdated;
use CraftCms\Cms\Activity\Models\ActivityEvent;
use CraftCms\Cms\Ai\Assistant\AssistantAgent;
use CraftCms\Cms\Ai\Assistant\McpTools;
use CraftCms\Cms\Ai\Http\Controllers\AssistantController;
use CraftCms\Cms\Ai\Settings;
use CraftCms\Cms\Ai\Tasks\Assistant;
use CraftCms\Cms\Ai\Tasks\GenerateTitle;
use CraftCms\Cms\Edition;
use CraftCms\Cms\Entry\Elements\Entry;
use CraftCms\Cms\Entry\Models\Entry as EntryModel;
use CraftCms\Cms\Entry\Models\EntryType;
use CraftCms\Cms\Field\Matrix;
use CraftCms\Cms\Support\Facades\Search;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use CraftCms\Cms\User\Users;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\AnonymousAgent;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Exceptions\StreamErrorException;
use Laravel\Ai\Models\Conversation;
use Laravel\Ai\Models\ConversationMessage;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Streaming\Events\Error;
use Laravel\Ai\Tools\Request as ToolRequest;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    config(['ai.providers' => ['anthropic' => ['driver' => 'anthropic', 'key' => 'sk-test']]]);
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
    ]));
    Edition::set(Edition::Pro);
    actingAs(User::findOne());
});

/**
 * Sends a message, as TanStack AI’s client does.
 */
function chat(string $threadId, string $message): TestResponse
{
    return postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [['id' => (string) Str::uuid(), 'role' => 'user', 'content' => $message]],
    ]);
}

/**
 * Returns the AG-UI events a response streamed.
 *
 * @return list<array<string, mixed>>
 */
function chatEvents(TestResponse $response): array
{
    preg_match_all('/^data: (.+)$/m', $response->streamedContent(), $matches);

    return array_values(array_filter(array_map(
        static fn (string $data): mixed => json_decode($data, true),
        $matches[1],
    )));
}

/**
 * Sends a message, and returns the tools the agent was offered, indexed by name.
 *
 * @return array<string, Tool>
 */
function offeredTools(): array
{
    $message = Str::random();
    AssistantAgent::fake(['Hello there.']);
    chat((string) Str::uuid(), $message)->assertOk()->streamedContent();

    $tools = [];
    AssistantAgent::assertPrompted(static function (AgentPrompt $prompt) use ($message, &$tools): bool {
        $tools = [];

        foreach ($prompt->agent->tools() as $tool) {
            $tools[$tool->name()] = $tool;
        }

        return $prompt->prompt === $message;
    });

    return $tools;
}

/**
 * Answers the approval request for a tool call, as TanStack AI’s client does.
 */
function decide(string $threadId, string $toolCallId, bool $approved): TestResponse
{
    return postJson(action([AssistantController::class, 'run']), [
        'threadId' => $threadId,
        'runId' => (string) Str::uuid(),
        'messages' => [],
        'resume' => [['interruptId' => $toolCallId, 'status' => 'resolved', 'payload' => ['approved' => $approved]]],
    ]);
}

it('streams a reply, and remembers the conversation under the thread ID the client chose', function () {
    AssistantAgent::fake(['Hello there.', 'You said hi.']);
    $threadId = (string) Str::uuid();

    $events = chatEvents(chat($threadId, 'Hi')->assertOk());

    expect(array_find($events, static fn (array $event): bool => $event['type'] === 'RUN_STARTED')['threadId'])->toBe($threadId)
        ->and(implode('', array_column(array_filter($events, static fn (array $event): bool => $event['type'] === 'TEXT_MESSAGE_CONTENT'), 'delta')))->toBe('Hello there.');

    chat($threadId, 'What did I say?')->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => $prompt->prompt === 'What did I say?'
        && $prompt->agent->currentConversation() === $threadId);
    expect(ConversationMessage::query()->oldest()->orderBy('id')->pluck('content')->all())
        ->toBe(['Hi', 'Hello there.', 'What did I say?', 'You said hi.']);
});

it('looks things up with Craft’s MCP tools, and shows the conversation again', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake([
        new ToolCall('call_1', 'elements_get', ['type' => 'entries', 'id' => $entry->id]),
        'It’s called Lighthouses.',
    ]);
    $threadId = (string) Str::uuid();

    chat($threadId, 'What’s the entry called?')->assertOk()->streamedContent();

    expect(ConversationMessage::query()->where('role', 'assistant')->sole()->tool_results[0]['result'])->toContain('Lighthouses');

    getJson(action([AssistantController::class, 'show'], ['threadId' => $threadId]))
        ->assertOk()
        ->assertJsonPath('messages.0.role', 'user')
        ->assertJsonPath('messages.0.parts', [['type' => 'text', 'content' => 'What’s the entry called?']])
        ->assertJsonPath('messages.1.role', 'assistant')
        ->assertJsonPath('messages.1.parts.0.type', 'tool-call')
        ->assertJsonPath('messages.1.parts.0.name', 'elements_get')
        ->assertJsonPath('messages.1.parts.1', ['type' => 'text', 'content' => 'It’s called Lighthouses.']);
});

it('offers the MCP tools the user can use, asking for approval before those that change anything', function () {
    $tools = offeredTools();

    expect(array_keys($tools))->toContain('elements_get', 'elements_update', 'content-model_audit')
        ->and($tools['elements_get']->shouldRequestApproval(new ToolRequest))->toBeNull()
        ->and($tools['elements_update']->shouldRequestApproval(new ToolRequest))->not->toBeNull();

    actingAs(UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->createElement());

    expect(array_keys(offeredTools()))
        ->toContain('elements_get')
        ->not->toContain('content-model_audit');
});

it('waits for the user to approve a tool call that changes anything, then resumes with their answer', function (bool $approved) {
    $entry = EntryModel::factory()->createElement(['title' => 'Lighthouses']);
    AssistantAgent::fake([
        new ToolCall('call_1', 'elements_update', ['type' => 'entries', 'id' => $entry->id, 'attributes' => ['title' => 'Renamed']]),
        'Done.',
    ]);
    $threadId = (string) Str::uuid();

    $paused = chatEvents(chat($threadId, 'Rename the entry')->assertOk());

    expect(array_find($paused, static fn (array $event): bool => $event['type'] === 'RUN_FINISHED')['outcome']['interrupts'][0]['toolCallId'])->toBe('call_1')
        ->and(Entry::find()->id($entry->id)->one()->title)->toBe('Lighthouses');

    decide($threadId, 'call_1', $approved)->assertOk()->streamedContent();

    AssistantAgent::assertPrompted(static fn (AgentPrompt $prompt): bool => $prompt->approvalDecisions?->get('call_1')?->isApproved() === $approved
        && $prompt->agent->currentConversation() === $threadId);
})->with(['approved' => true, 'rejected' => false]);

it('records changes made through the assistant’s tools as the assistant’s', function () {
    $entry = EntryModel::factory()->createElement(['title' => 'Lighthouses']);
    request()->setUserResolver(static fn () => auth()->user());

    $result = app(McpTools::class)->call('elements_update', ['type' => 'entries', 'id' => $entry->id, 'attributes' => ['title' => 'Renamed']]);

    expect($result)->not->toStartWith('Error:')
        ->and(Entry::find()->id($entry->id)->one()->title)->toBe('Renamed')
        ->and(ActivityEvent::query()->eventTypes(ElementUpdated::class)->newestFirst()->firstOrFail()->snapshots['origin'])->toBe('AI assistant');
});

/**
 * Stores a turn that paused for the user to approve `call_pending`, after they rejected `call_rejected`.
 */
function storePausedTurn(string $threadId): void
{
    $user = User::findOne();
    $call = static fn (string $id, array $answer): array => [
        'id' => $id,
        'name' => 'elements_update',
        'arguments' => ['type' => 'entries', 'id' => 1],
        'approval_reason' => 'This changes content or settings.',
        ...$answer,
    ];
    ConversationMessage::query()->create([
        'id' => (string) Str::uuid7(),
        'conversation_id' => $threadId,
        'participant_type' => $user::class,
        'participant_id' => $user->id,
        'agent' => AssistantAgent::class,
        'role' => 'assistant',
        'content' => '',
        'attachments' => [],
        'steps' => [['content' => 'I’ll rename it.', 'tool_calls' => [
            $call('call_rejected', ['result' => 'The user rejected this tool call.', 'denied' => true]),
            $call('call_pending', []),
        ]]],
        'usage' => [],
        'meta' => [],
        'status' => 'paused',
        'created_at' => now()->addMinute(),
    ]);
}

it('shows the approvals a conversation is waiting for again, and the calls the user rejected', function () {
    AssistantAgent::fake(['Hello there.']);
    $threadId = (string) Str::uuid();
    chat($threadId, 'Hi')->assertOk()->streamedContent();
    storePausedTurn($threadId);

    getJson(action([AssistantController::class, 'show'], ['threadId' => $threadId]))
        ->assertJsonPath('interrupts.pending.0.toolCallId', 'call_pending')
        ->assertJsonPath('messages.2.parts.0', ['type' => 'text', 'content' => 'I’ll rename it.'])
        ->assertJsonPath('messages.2.parts.1.approval.approved', false)
        ->assertJsonPath('messages.2.parts.2.state', 'approval-requested');
});

it('stops waiting for approvals once the user has moved on to another message', function () {
    AssistantAgent::fake(['Hello there.']);
    $threadId = (string) Str::uuid();
    chat($threadId, 'Hi')->assertOk()->streamedContent();
    storePausedTurn($threadId);
    $this->travel(2)->minutes();
    chat($threadId, 'Never mind')->assertOk()->streamedContent();

    getJson(action([AssistantController::class, 'show'], ['threadId' => $threadId]))
        ->assertJsonPath('interrupts', null)
        ->assertJsonPath('messages.2.parts.2.state', 'error');
});

it('suggests the elements the user can view that match what they typed after @', function () {
    $lighthouses = EntryModel::factory()->createElement(['title' => 'Lighthouses of Maine']);
    $noteType = EntryType::factory()->create(['handle' => 'note']);
    $harbors = EntryModel::factory()->withField(
        'notes', Matrix::class, ['entryTypes' => [$noteType->id]],
        value: ['new1' => ['type' => 'note', 'title' => 'Lighthouses block']],
    )->createElementWithFields(['title' => 'Harbors'])->element;
    $block = $harbors->getFieldValue('notes')->one();

    foreach ([$lighthouses, $harbors, $block] as $element) {
        Search::indexElementAttributes($element);
    }
    $url = action([AssistantController::class, 'mentions'], ['query' => '@Lighthouses']);

    // The block is found through Harbors, whose keywords include it.
    getJson($url)
        ->assertOk()
        ->assertJsonPath('*.label', ['Lighthouses of Maine', 'Harbors'])
        ->assertJsonPath('0.value', '@Lighthouses of Maine')
        ->assertJsonPath('0.data.elementId', $lighthouses->id)
        ->assertJsonPath('0.data.elementType', Entry::class)
        ->assertJsonPath('0.data.status.label', 'Live');

    actingAs(UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->createElement());

    getJson($url)->assertOk()->assertJsonPath('*.label', []);
});

it('renames and deletes the user’s own conversations, and nobody else’s', function () {
    AssistantAgent::fake(['Hello there.']);
    $threadId = (string) Str::uuid();
    chat($threadId, 'Hi')->assertOk()->streamedContent();
    $owner = auth()->user();

    postJson(action([AssistantController::class, 'rename']), ['threadId' => $threadId, 'title' => ' Greetings '])
        ->assertOk()
        ->assertJsonPath('title', 'Greetings');

    expect(Conversation::query()->find($threadId)?->getAttribute('title'))->toBe('Greetings');

    actingAs(UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->createElement());

    postJson(action([AssistantController::class, 'rename']), ['threadId' => $threadId, 'title' => 'Mine'])->assertNotFound();
    postJson(action([AssistantController::class, 'destroy']), ['threadId' => $threadId])->assertNotFound();

    actingAs($owner);

    postJson(action([AssistantController::class, 'destroy']), ['threadId' => $threadId])->assertOk();

    expect(Conversation::query()->find($threadId))->toBeNull()
        ->and(ConversationMessage::query()->where('conversation_id', $threadId)->count())->toBe(0);
});

it('keeps the title the user gave a conversation when its generated title arrives', function () {
    $threadId = (string) Str::uuid();
    AssistantAgent::fake(['There are four lighthouses.']);
    // The user renames it while its title is generated.
    AnonymousAgent::fake(function () use ($threadId): string {
        Conversation::query()->whereKey($threadId)->update(['title' => 'Mine']);

        return 'Lighthouse lookup';
    });

    chat($threadId, 'Find the lighthouses')->assertOk()->streamedContent();

    expect(Conversation::query()->find($threadId)?->getAttribute('title'))->toBe('Mine');
});

it('names a new conversation from its first message’s text once its first reply is done, with the chat titles task’s model', function () {
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
        'tasks' => [GenerateTitle::handle() => ['model' => 'claude-title-model']],
    ]));
    AssistantAgent::fake(['There are four lighthouses.', 'Two of them are live.']);
    AnonymousAgent::fake(['Lighthouse lookup', 'Another title']);
    $threadId = (string) Str::uuid();

    chat($threadId, "<context>[{\"label\":\"Lighthouses\"}]</context>\n\nFind the lighthouses")->assertOk()->streamedContent();
    chat($threadId, 'Which are live?')->assertOk()->streamedContent();

    expect(Conversation::query()->find($threadId)?->getAttribute('title'))->toBe('Lighthouse lookup');
    AnonymousAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->model === 'claude-title-model'
        && str_contains($prompt->prompt, 'Find the lighthouses')
        && ! str_contains($prompt->prompt, '<context>'));
});

it('lists the user’s own conversations, grouped by the day of their latest message in their time zone', function () {
    AssistantAgent::fake(['Hello there.', 'Hi again.', 'Hey.']);
    // Auckland is 13 hours ahead, so it’s already October 25 there.
    app(Users::class)->saveUserPreferences(User::findOne(), ['timeZone' => 'Pacific/Auckland']);
    $older = (string) Str::uuid();
    $yesterday = (string) Str::uuid();
    $today = (string) Str::uuid();

    $this->travelTo(Date::parse('2026-10-20 12:00', 'UTC'));
    chat($older, 'Find the lighthouses')->assertOk()->streamedContent();
    // October 24 in UTC, but in Auckland the day before the one it’s now.
    $this->travelTo(Date::parse('2026-10-24 09:00', 'UTC'));
    chat($yesterday, 'Summarize the news')->assertOk()->streamedContent();
    $this->travelTo(Date::parse('2026-10-24 23:00', 'UTC'));
    chat($today, 'Draft a post')->assertOk()->streamedContent();

    getJson(action([AssistantController::class, 'conversations']))
        ->assertOk()
        ->assertJsonPath('groups.*.heading', ['Today', 'Yesterday', 'October 21, 2026'])
        ->assertJsonPath('groups.*.conversations.*.id', [$today, $yesterday, $older]);

    actingAs(UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->createElement());

    getJson(action([AssistantController::class, 'conversations']))->assertJsonPath('groups', []);
});

it('logs an error the provider ends a reply with, which the chat only shows as a message', function () {
    Log::spy();
    AssistantAgent::fake([
        static fn () => throw new StreamErrorException(new Error('error_1', 'rate_limit_exceeded', 'Rate limit reached.', true, time())),
    ]);
    $threadId = (string) Str::uuid();

    chat($threadId, 'Hello')->assertOk()->streamedContent();

    Log::shouldHaveReceived('error')->once()->withArgs(static fn (string $message, array $context): bool => str_contains($message, 'Rate limit reached.')
        && $context['threadId'] === $threadId
        && $context['type'] === 'rate_limit_exceeded');
});

it('turns a message away while the assistant can’t run, without starting a conversation', function () {
    app()->instance(Settings::class, new Settings([
        'defaults' => ['text' => ['provider' => 'anthropic']],
        'tasks' => [Assistant::handle() => ['enabled' => false]],
    ]));

    chat((string) Str::uuid(), 'Hi')->assertUnprocessable()->assertJsonStructure(['message']);

    expect(Conversation::query()->count())->toBe(0);
});

it('turns away a request with neither a message nor answers to approval requests', function () {
    postJson(action([AssistantController::class, 'run']), [
        'threadId' => (string) Str::uuid(),
        'runId' => (string) Str::uuid(),
        'messages' => [],
    ])->assertBadRequest();
});

it('requires permission to use the assistant', function () {
    actingAs(UserModel::factory()->withPermissions(['accessCp'])->createElement());

    chat((string) Str::uuid(), 'Hi')->assertForbidden();
});

it('won’t continue or show another user’s conversation', function () {
    AssistantAgent::fake(['Hello there.']);
    $threadId = (string) Str::uuid();
    chat($threadId, 'Hi')->assertOk()->streamedContent();

    actingAs(UserModel::factory()->withPermissions(['accessCp', Assistant::PERMISSION])->createElement());

    chat($threadId, 'Hi')->assertNotFound();
    getJson(action([AssistantController::class, 'show'], ['threadId' => $threadId]))->assertNotFound();
});
