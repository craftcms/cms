---
name: ai-sdk-development
description: TRIGGER when working with ai-sdk, Laravel's official first-party AI SDK. Activate when building or editing AI agents, chatbots, text generation, image generation, audio/TTS, transcription/STT, embeddings, RAG, vector stores, reranking, structured output, streaming, conversation memory, tools, MCP servers, queueing, broadcasting, and provider failover across OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama, ElevenLabs, Cohere, Jina, and VoyageAI. Invoke when the user references ai-sdk, the `Laravel\Ai\` namespace, or this project's AI features — not for other AI packages used directly.
license: MIT
metadata:
  author: laravel
---

# Developing with the Laravel AI SDK

The Laravel AI SDK (`laravel/ai`) is the official AI package for Laravel, providing a unified API for agents, images, audio, transcription, embeddings, reranking, vector stores, and file management across multiple AI providers.

## Searching the Documentation

This package is new. Always search the documentation before implementing any feature. Never guess at APIs — the documentation is the single source of truth.

- Use broad, simple queries that match the documentation section headings below.
- Do not add package names to queries — package information is shared automatically. Use `test agent fake`, not `laravel ai test agent fake`.
- Run multiple queries at once — the most relevant results are returned first.

### Documentation Sections

Use these section headings as query terms for accurate results:

- Introduction, Installation, Configuration, Provider Support
- Agents: Prompting, Conversation Context, Structured Output, Attachments, Streaming, Broadcasting, Queueing, Tools, Provider Tools, Middleware, Anonymous Agents, Agent Configuration
- Images
- Audio (TTS)
- Transcription (STT)
- Embeddings: Querying Embeddings, Caching Embeddings
- Reranking
- Files
- Vector Stores: Adding Files to Stores
- Failover
- Testing: Agents, Images, Audio, Transcriptions, Embeddings, Reranking, Files, Vector Stores
- Events

## Decision Workflow

Determine the right entry point before writing code:

Text generation or chat? → Agent class with `Promptable` trait
Chat with conversation history? → Agent + `Conversational` interface (manual) or `RemembersConversations` trait (automatic)
Structured JSON output? → Agent + `HasStructuredOutput` interface
Image generation? → `Image::of()->generate()`
Audio synthesis? → `Audio::of()->generate()`
Transcription? → `Transcription::fromPath()->generate()`
Embeddings? → `Embeddings::for()->generate()`
Reranking? → `Reranking::of()->rerank()`
File storage? → `Document::fromPath()->put()`
Vector stores? → `Stores::create()`

## Basic Usage Examples

### Agents

```php
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

class SalesCoach implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'You are a sales coach.';
    }
}

// Prompting
$response = (new SalesCoach)->prompt('Analyze this transcript...');
echo $response->text;

// Container resolution with dependency injection
$agent = SalesCoach::make(user: $user);

// Override provider, model, or timeout per-prompt
$response = (new SalesCoach)->prompt(
    'Analyze this transcript...',
    provider: Lab::Anthropic,
    model: 'claude-haiku-4-5-20251001',
    timeout: 120,
);

// Streaming (returns SSE response from a route)
return (new SalesCoach)->stream('Analyze this transcript...');

// Queueing
(new SalesCoach)->queue('Analyze this transcript...')
    ->then(fn ($response) => /* ... */);

// Anonymous agents
use function Laravel\Ai\{agent};

$response = agent(instructions: 'You are a helpful assistant.')->prompt('Hello');
```

### Conversation Context

Manual conversation history via the `Conversational` interface:

```php
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

class SalesCoach implements Agent, Conversational
{
    use Promptable;

    public function __construct(public User $user) {}

    public function instructions(): string { return 'You are a sales coach.'; }

    public function messages(): iterable
    {
        return History::where('user_id', $this->user->id)
            ->latest()->limit(50)->get()->reverse()
            ->map(fn ($m) => new Message($m->role, $m->content))
            ->all();
    }
}
```

Automatic conversation persistence via the `RemembersConversations` trait:

```php
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Promptable;

class SalesCoach implements Agent, Conversational
{
    use Promptable, RemembersConversations;

    public function instructions(): string { return 'You are a sales coach.'; }
}

// Start a new conversation
$response = (new SalesCoach)->forUser($user)->prompt('Hello!');
$conversationId = $response->conversationId;

// Continue an existing conversation
$response = (new SalesCoach)->continue($conversationId, as: $user)->prompt('Tell me more.');
```

### Structured Output

```php
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

class Reviewer implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string { return 'Review and score content.'; }

    public function schema(JsonSchema $schema): array
    {
        return [
            'feedback' => $schema->string()->required(),
            'score' => $schema->integer()->min(1)->max(10)->required(),
        ];
    }
}

$response = (new Reviewer)->prompt('Review this...');
echo $response['score']; // Access like an array
```

### Images

```php
use Laravel\Ai\Image;

$image = Image::of('A sunset over mountains')
    ->landscape()
    ->quality('high')
    ->generate();

$path = $image->store(); // Store to default disk
```

### Audio

```php
use Laravel\Ai\Audio;

$audio = Audio::of('Hello from Laravel.')
    ->female()
    ->instructions('Speak warmly')
    ->generate();

$path = $audio->store();
```

### Transcription

```php
use Laravel\Ai\Transcription;

$transcript = Transcription::fromStorage('audio.mp3')
    ->diarize()
    ->generate();

echo (string) $transcript;
```

### Embeddings

```php
use Laravel\Ai\Embeddings;
use Illuminate\Support\Str;

$response = Embeddings::for(['Text one', 'Text two'])
    ->dimensions(1536)
    ->cache()
    ->generate();

// Single string via Stringable
$embedding = Str::of('Napa Valley has great wine.')->toEmbeddings();
```

### Reranking

```php
use Laravel\Ai\Reranking;

$response = Reranking::of(['Django is Python.', 'Laravel is PHP.', 'React is JS.'])
    ->limit(5)
    ->rerank('PHP frameworks');

$response->first()->document; // "Laravel is PHP."
```

### Files and Vector Stores

```php
use Laravel\Ai\Files\Document;
use Laravel\Ai\Stores;

// Store a file with the provider
$file = Document::fromPath('/path/to/doc.pdf')->put();

// Create a vector store and add files
$store = Stores::create('Knowledge Base');
$store->add($file->id);
$store->add(Document::fromStorage('manual.pdf')); // Store + add in one step
```

## Agent Configuration

### PHP Attributes

```php
use Laravel\Ai\Attributes\{Provider, Model, MaxSteps, MaxTokens, Temperature, Timeout};
use Laravel\Ai\Enums\Lab;

#[Provider(Lab::Anthropic)]
#[Model('claude-haiku-4-5-20251001')]
#[MaxSteps(10)]
#[MaxTokens(4096)]
#[Temperature(0.7)]
#[Timeout(120)]
class MyAgent implements Agent
{
    use Promptable;
    // ...
}
```

The `#[UseCheapestModel]` and `#[UseSmartestModel]` attributes are also available for automatic model selection.

Use `#[RepairToolCalls]` to let an agent recover when a model calls an unknown local tool. The failed call is returned to the model with the available local tool names, and the implicit step budget includes one repair step. Explicit `#[MaxSteps]` limits remain unchanged.

```php
use Laravel\Ai\Attributes\RepairToolCalls;

#[RepairToolCalls]
class SupportAgent implements Agent, HasTools
{
    use Promptable;

    // ...
}
```

The `#[WithoutBroadcasting]` attribute stops the given stream event types from broadcasting (e.g. data-heavy `ToolResult` payloads that exceed the WebSocket frame limit). The events are still streamed and persisted; they just never hit the channel:

```php
use Laravel\Ai\Attributes\WithoutBroadcasting;
use Laravel\Ai\Streaming\Events\{ToolCall, ToolResult};

#[WithoutBroadcasting(ToolResult::class, ToolCall::class)]
class SearchAgent implements Agent, HasTools
{
    use Promptable;
    // ...
}
```

### Tools

Implement the `HasTools` interface and scaffold tools with `php artisan make:tool`:

```php
use Laravel\Ai\Contracts\HasTools;

class MyAgent implements Agent, HasTools
{
    use Promptable;

    public function tools(): iterable
    {
        return [new MyCustomTool];
    }
}
```

### Provider Tools

```php
use Laravel\Ai\Providers\Tools\{WebSearch, WebFetch, FileSearch};

public function tools(): iterable
{
    return [
        (new WebSearch)->max(5)->allow(['laravel.com']),
        new WebFetch,
        new FileSearch(stores: ['store_id']),
    ];
}
```

### MCP Servers

Register the server once, then return its tools from `tools()`. The SDK automatically wraps each `Laravel\Mcp\Client\Primitives\Tool` and presents it to the model as `mcp_tools_<name>`. Return your own `Laravel\Mcp\Server\Tool` instances in the same way and they retain their names and run in-process.

```php
use Laravel\Mcp\Client;
use Laravel\Mcp\Facades\Mcp;

// In a service provider or routes/ai.php
Mcp::registerClient('linear', fn () => Client::web('https://mcp.linear.app/mcp')
    ->withToken(config('services.linear.token')));

class SupportAgent implements Agent, HasTools
{
    use Promptable;

    public function tools(): iterable
    {
        return Mcp::client('linear')->tools();
    }
}
```

The client connects on its first call, so call `connect()` only when you need to control the timing. Use `Client::local('npx', ['-y', 'some-server'])` for servers that run over stdio.

### Conversation Memory

```php
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Conversational;

class ChatBot implements Agent, Conversational
{
    use Promptable, RemembersConversations;
    // ...
}

$response = (new ChatBot)->forUser($user)->prompt('Hello!');
$response = (new ChatBot)->continue($conversationId, as: $user)->prompt('More...');
```

### Failover

```php
$response = (new MyAgent)->prompt('Hello', provider: [Lab::OpenAI, Lab::Anthropic]);
```

## Testing and Faking

Each capability supports `fake()` with assertions:

```php
use App\Ai\Agents\SalesCoach;
use Laravel\Ai\{Image, Audio, Transcription, Embeddings, Reranking, Files, Stores};

// Agents
SalesCoach::fake(['Response 1', 'Response 2']);
SalesCoach::assertPrompted('query');
SalesCoach::assertNotPrompted('query');
SalesCoach::assertNeverPrompted();
SalesCoach::fake()->preventStrayPrompts();

// Images
Image::fake();
Image::assertGenerated(fn ($prompt) => $prompt->contains('sunset'));
Image::assertNothingGenerated();

// Audio
Audio::fake();
Audio::assertGenerated(fn ($prompt) => $prompt->contains('Hello'));

// Transcription
Transcription::fake(['Transcribed text.']);
Transcription::assertGenerated(fn ($prompt) => $prompt->isDiarized());

// Embeddings
Embeddings::fake();
Embeddings::assertGenerated(fn ($prompt) => $prompt->contains('Laravel'));

// Reranking
Reranking::fake();
Reranking::assertReranked(fn ($prompt) => $prompt->contains('PHP'));

// Files
Files::fake();
Files::assertStored(fn ($file) => $file->mimeType() === 'text/plain');

// Stores
Stores::fake();
Stores::assertCreated('Knowledge Base');
$store = Stores::get('id');
$store->assertAdded('file_id');
```

## Key Patterns

- Namespace: `Laravel\Ai\`
- Package: `composer require laravel/ai`
- Agent pattern: Implement the `Agent` interface and use the `Promptable` trait
- Optional interfaces: `HasTools`, `HasMiddleware`, `HasStructuredOutput`, `Conversational`
- Entry-point classes: `Image`, `Audio`, `Transcription`, `Embeddings`, `Reranking`, `Stores`
- Provider enum: `Laravel\Ai\Enums\Lab` (prefer over plain strings)
- Artisan commands: `php artisan make:agent`, `php artisan make:tool`
- Global helper: `agent()` for anonymous agents

## OpenAI-Compatible Provider

Point the SDK at any OpenAI-compatible endpoint (LM Studio, vLLM, Together, etc.) with the config-driven `openai-compatible` driver. Define named instances in `config/ai.php`, no code required:

```php
'my-llm' => [
    'driver' => 'openai-compatible',
    'url' => env('MY_LLM_URL'),        // required
    'key' => env('MY_LLM_API_KEY'),    // optional Bearer token
    'models' => [
        'text' => ['default' => 'some-chat-model'],
        'embeddings' => [
            'default' => 'some-embedding-model',
            'dimensions' => 1024, // optional; omit to use native dimensions
        ],
        'transcription' => ['default' => 'some-transcription-model'],
    ],
],
```

Reference it by config key (or `Lab::OpenAiCompatible`). A model is required via the corresponding `models` configuration or per-call `model:`:

```php
agent()->prompt('Hello', provider: 'my-llm', model: 'some-model');

Embeddings::for(['Hello'])->generate(
    provider: 'my-llm',
    model: 'some-embedding-model',
);
```

It uses OpenAI-standard shapes and supports text, streaming, tools, structured output, image attachments, text embeddings, and audio transcription. Embedding dimensions are optional; omit them to use the model's native dimensions. For extra request-body fields, implement `HasProviderOptions` — the returned array is merged into the body.

Transcription uploads standard multipart (`file` + `model` + optional `language`) and defaults to `response_format: json`. Because endpoints vary, provider options override the defaults — pass `response_format: 'verbose_json'` for segments, or use `diarize()` on servers that implement `diarized_json`:

```php
Transcription::fromDisk('recordings', $path)
    ->withProviderOptions(['response_format' => 'verbose_json'])
    ->generate(provider: 'my-llm');
```

## Common Pitfalls

### Wrong Namespace

The namespace is `Laravel\Ai`, not `Illuminate\Ai` or `Laravel\AI`.

```php
// Correct
use Laravel\Ai\Image;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

// Wrong — these do not exist
use Illuminate\Ai\Image;
use Laravel\AI\Agent;
```

### Unsupported Provider Capability

Calling a capability not supported by a provider throws a `LogicException`. Refer to the provider support table below.

## Provider Support

| Feature    | Providers                                                       |
| ---------- | --------------------------------------------------------------- |
| Text       | OpenAI, Anthropic, Gemini, Azure, Groq, xAI, DeepSeek, Mistral, Ollama, OpenRouter, OpenAI-compatible |
| Images     | OpenAI, Gemini, xAI                                            |
| TTS        | OpenAI, ElevenLabs, Mistral                                     |
| STT        | OpenAI, ElevenLabs, Mistral, Groq, OpenAI-compatible            |
| Embeddings | OpenAI, OpenAI-compatible, Gemini, Azure, Cohere, Mistral, Jina, VoyageAI |
| Reranking  | Cohere, Jina                                                    |
| Files      | OpenAI, Anthropic, Gemini                                       |

Use the `Laravel\Ai\Enums\Lab` enum to reference providers in code instead of plain strings:

```php
use Laravel\Ai\Enums\Lab;

Lab::Anthropic;
Lab::OpenAI;
Lab::Gemini;
Lab::OpenAiCompatible; // configurable OpenAI-compatible endpoint
// ...
```
