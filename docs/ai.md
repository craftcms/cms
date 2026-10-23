# AI connections

Craft gives plugins one shared way to reach AI providers. Administrators choose the providers and models under **Settings → Extend → Connections**, and plugins ask for them by *task*. Plugins don’t need their own API key settings.

Craft builds on [`laravel/ai`](https://laravel.com/docs/13.x/ai-sdk). Agents, tools, structured output, streaming, attachments, and test fakes all work as that package documents them. Craft adds three things:

- **Tasks**: things Craft and plugins do with AI, such as “Ask AI” or “Describe image”, that administrators map to a provider and model, or disable.
- **Resolution**: turning a task into a provider and model, with environment variable support.
- **Availability**: AI is off until an administrator chooses a provider, so features can hide themselves.

Customers bring their own provider keys. Craft doesn’t broker requests, store keys, or log prompts.

## Providers

A provider is an entry in the `ai.providers` config, which belongs to the Laravel application. Keys come from environment variables, as `laravel/ai` documents:

```php
// config/ai.php
'providers' => [
    'anthropic' => [
        'driver' => 'anthropic',
        'key' => env('ANTHROPIC_API_KEY'),
    ],
    'local' => [
        'driver' => 'ollama',
        'url' => env('OLLAMA_URL', 'http://localhost:11434'),
    ],
],
```

Every `laravel/ai` driver works, including custom drivers. Craft never overrides the `ai.*` config, and never falls back to `ai.default`. An application can have its own AI features with its own default provider, and Craft’s tasks stay off until an administrator chooses a provider in Craft.


## Settings

Craft’s AI settings live in project config, under `ai`:

```yaml
ai:
  defaults:
    text: { provider: '$AI_TEXT_PROVIDER', model: null }
  tasks:
    'altText:describe': { enabled: true, provider: null, model: null }
```

- **Defaults** set a provider and model for each modality that a registered task uses. A modality without a default provider is off.
- **Tasks** can be disabled, or given their own provider and model. A task without its own provider uses the default for its modality.
- A `null` model means the provider’s default model.
- The settings screen suggests the models that `laravel/ai` names for the chosen provider, such as its default, cheapest, and smartest text models. Any other model name works too.
- Only providers that support a modality can be chosen for it, such as providers that implement `Laravel\Ai\Contracts\Providers\ImageProvider` for image tasks. `Ai::providers(Modality::Image)` returns them.

Provider, model, and enabled values can reference environment variables, such as `$AI_TEXT_PROVIDER`, so environments can use different providers. You can also vary a provider’s driver, URL, and default model in `config/ai.php`.

When an enabled task can’t run, admins see a control panel alert, and the settings screen lists each task with the reason. A task can’t run when it has no provider and its modality has no default, or when its provider isn’t in `config/ai.php`. It’s also listed when its provider’s `key` is empty and its driver needs one, because its requests would fail. `Ai::problems()` returns the reasons, indexed by task handle. Disable a task to stop the alert without setting it up.

Don’t choose a provider for your task when your plugin is installed. Admins are told when it needs one, and choosing a provider is theirs to do.

When a plugin is uninstalled, its task mappings stay in project config and are ignored.

When a task with the `Embeddings` modality is registered, the settings screen warns that changing its provider or model breaks existing embeddings. Embeddings from different models can’t be compared, so if your plugin stores them, store the model that created them too (`Ai::task($handle)->resolve()->model`, or the provider’s default model when it’s `null`), and re-index when it changes.

To turn a task off quickly in an environment where admin changes are disallowed, set its **Enabled** value to an environment variable, such as `$AI_ALT_TEXT_ENABLED`.

## Registering a task

A task is a class that extends `CraftCms\Cms\Ai\Task`:

```php
use CraftCms\Cms\Ai\Task;

use function CraftCms\Cms\t;

class DescribeImage extends Task
{
    public static function handle(): string
    {
        return 'altText:describe';
    }

    public static function label(): string
    {
        return t('Describe image', category: 'alt-text');
    }

    public static function description(): ?string
    {
        return t('Writes alt text for an image. Needs a model with vision.', category: 'alt-text');
    }
}
```

Register it from your plugin’s `boot()` method:

```php
use CraftCms\Cms\Ai\AiTasks;

public function boot(AiTasks $tasks): void
{
    $tasks->register(DescribeImage::class);
}
```

| Method         | Description |
| -------------- | ----------- |
| `handle()`     | Required. Globally unique. Prefix it with your plugin handle. Registering another class with the same handle throws an `InvalidArgumentException`. |
| `label()`      | Required. Shown to administrators. It’s called when the settings screen renders, so translations use the current user’s language. |
| `modality()`   | `Modality::Text` by default. Also `Image`, `Audio`, `Transcription`, `Embeddings`, `Reranking`, or `Classification`. It decides which default provider applies. |
| `description()` | Optional. Shown to administrators. Mention what the model needs, such as vision, because Craft can’t check models for it. |

The settings screen shows the name of the plugin that owns the task class. New tasks are enabled and use the default provider for their modality.

## Running a task

Check availability before you show an AI feature:

```php
if (! Ai::available('altText:describe')) {
    return;
}
```

A task is unavailable when it’s disabled, when neither it nor its modality has a provider, or when its provider isn’t in the `ai.providers` config.
Then run `laravel/ai` agents and requests through the task. `AltTextAgent` stands for your own agent class, one that implements `Laravel\Ai\Contracts\Agent`:
Then run `laravel/ai` agents and requests through the task:

```php
use CraftCms\Cms\Support\Facades\Ai;
use Laravel\Ai\Files\Image as ImageFile;
use Laravel\Ai\Image;
use Laravel\Ai\Reranking;

// Agents (text tasks)
$response = Ai::task('altText:describe')
    ->prompt(agent: new AltTextAgent, prompt: 'Describe this image', attachments: [ImageFile::fromPath($path)]);

$stream = Ai::task('chat:reply')->stream(agent: new Assistant, prompt: $message);

// Media and data (image, audio, transcription, and embeddings tasks)
$image = Ai::task('blog:heroImage')->generate(request: Image::of('A lighthouse at dusk')->landscape());

// Reranking and classification tasks
$ranked = Ai::task('search:rerank')->rerank(request: Reranking::of($documents), query: 'craft cms');
$labels = Ai::task('content:classify')->classify(request: $pendingClassification);
```

Each method resolves the task first. If it can’t, it throws `CraftCms\Cms\Ai\Exceptions\AiUnavailableException` before any request is sent. The exception’s `$reason` is an `AiUnavailableReason`: `UnknownTask`, `TaskDisabled`, `NoDefault`, `UnknownProvider`, or `Canceled` (see [Events](#events)). Provider and network errors are `laravel/ai`’s own exceptions.

Running an operation of another modality, such as generating an image with a text task, throws a `LogicException`.

To use a `laravel/ai` API that the task client doesn’t cover, resolve the task and pass the provider and model yourself:

```php
$resolved = Ai::task('altText:describe')->resolve();

$response = (new AltTextAgent)->prompt(prompt: 'Describe this image', provider: $resolved->provider, model: $resolved->model);
```

Plugins may also use `laravel/ai` directly, without tasks. Those calls don’t follow Craft’s AI settings.

## Queueing

`queue()` prompts an agent in a queued job:

```php
Ai::task('altText:describe')
    ->queue(agent: new AltTextAgent, prompt: 'Describe this image', attachments: [ImageFile::fromStorage($path)])
    ->then(fn ($response) => $asset->saveAltText($response->text));
```

The job resolves the task when it runs, not when it’s dispatched. If an administrator disables the task or changes its provider in the meantime, queued jobs follow the new settings, and jobs for a disabled task fail with `AiUnavailableException`. Attachments must be serializable, such as stored files.

To queue media generation, dispatch your own job that calls `generate()`. It resolves the task when it runs as well.

## Events

Every request a task client sends dispatches Laravel events, so plugins can track usage and cost, or enforce limits:

| Event | When | Properties |
| ----- | ---- | ---------- |
| `CraftCms\Cms\Ai\Events\TaskRunning` | Before the request is sent | `task` (the task class), `provider` (the resolved provider and model), `request` (the agent or pending request), `cancel` |
| `CraftCms\Cms\Ai\Events\TaskCompleted` | After the provider responds. For streams, when the stream ends | `task`, `provider`, `response`, `usage`, `duration` (milliseconds) |
| `CraftCms\Cms\Ai\Events\TaskFailed` | When a sent request fails. For streams, when the stream fails while it’s read | `task`, `provider`, `exception`, `duration` (milliseconds) |

`usage` is the response’s usage object, such as `TextUsage` with input, output, cache, and reasoning token counts. `laravel/ai` reports tokens, not cost, so convert them with your own prices.

Set `cancel` to `true` to stop a request, for example when a user has reached a limit. The task client then throws `AiUnavailableException` with the `Canceled` reason, and no request is sent:

```php
use CraftCms\Cms\Ai\Events\TaskRunning;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;

Event::listen(function (TaskRunning $event): void {
    if (RateLimiter::tooManyAttempts("ai:{$event->task::handle()}", 100)) {
        $event->cancel = true;
    }
});
```

`TaskFailed` reports the failure, and the task client then rethrows the exception unchanged. A request that fails may still have been billed, but `laravel/ai` exceptions don’t carry usage. Unavailable tasks and canceled requests don’t dispatch `TaskFailed`, because nothing was sent. Calls made directly through `laravel/ai`, without a task, don’t dispatch any of these events.

## Testing

Use `laravel/ai`’s fakes. They replace the gateway, so tasks still resolve:

```php
use CraftCms\Cms\Ai\Settings;

app()->instance(Settings::class, new Settings([
    'defaults' => ['text' => ['provider' => 'anthropic']],
]));
AltTextAgent::fake(['A lighthouse at dusk.']);

$response = Ai::task('altText:describe')->prompt(agent: new AltTextAgent, prompt: 'Describe this image');

AltTextAgent::assertPrompted(fn ($prompt) => $prompt->provider->name() === 'anthropic');
```

## Ask AI

Craft registers a core task, “Ask AI” (`craft:askAi`). It powers the “Ask AI” element details panel and fields’ “Ask AI” actions, for users with the “Ask AI” permission. The panel is a conversation: editors write a prompt, review the proposed values in the reply, apply the ones they want to the form, and follow up to refine them (“keep it shorter”). Nothing is saved until they save the element.

Prompts can ask for new content, changes to existing content (“shorten this”, “fix the grammar”), or a review (“check this against our style guide”). Along with the values it changes, the model returns a summary of what it did, and findings: specific problems, each tied to a field or to the content as a whole. A review can come back with feedback and nothing to apply, and editors can ask the model to fix a finding.

Craft doesn’t store conversations on the server. The panel keeps them in the browser tab’s session storage, for the signed-in user only, and sends the earlier turns with each follow-up. The model sees them before the current values.

Replies stream: the model writes its summary first, which appears as it’s written, then submits its changes and findings through a tool call. The endpoint responds with newline-delimited JSON events, so a proxy or compression layer that buffers responses holds the summary back until the reply is complete. Craft sends `X-Accel-Buffering: no` for nginx; other proxies may need buffering turned off for Craft’s action URLs.

For image assets, the panel can also send the image, scaled down to fit 1024×1024, so a model with vision can describe it. Editors can uncheck “Include the image” to keep the file from the provider.

It can write the Title and Alternative Text fields, and custom fields that implement `CraftCms\Cms\Field\Contracts\InputSchemaFieldInterface`, which returns the JSON schema of the values the field accepts. In Craft, those are Plain Text, Markdown, Email, Link, Number, Money, Lightswitch, Date, Time, Color, Country, Table, and the option fields. Implement it to support your own field type. The same schema describes the field to Craft’s MCP server. If your field’s GraphQL mutation argument is an input type, `CraftCms\Cms\Gql\GqlInputSchema::fromType()` turns it into a JSON schema.

## Permissions

Only administrators can change AI settings. The screen is read-only when admin changes are disallowed. Craft has no general “Use AI” permission: register permissions for your own AI features, such as “Generate alt text”.
