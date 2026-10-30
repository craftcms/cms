<?php

declare(strict_types=1);

namespace Workbench\App\Ai;

use CraftCms\Cms\Support\Json;
use Generator;
use Illuminate\JsonSchema\Types\ObjectType;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Laravel\Ai\Contracts\Gateway\StepTextGateway;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Gateway\StepContext;
use Laravel\Ai\Gateway\StepResponse;
use Laravel\Ai\Gateway\TextGenerationOptions;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Messages\ToolResultMessage;
use Laravel\Ai\Messages\UserMessage;
use Laravel\Ai\Responses\Data\FinishReason;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\Data\ToolCall;
use Laravel\Ai\Responses\Data\ToolResult;
use Laravel\Ai\Streaming\Events\ReasoningDelta;
use Laravel\Ai\Streaming\Events\ReasoningEnd;
use Laravel\Ai\Streaming\Events\ReasoningStart;
use Laravel\Ai\Streaming\Events\StreamStart;
use Laravel\Ai\Streaming\Events\TextDelta;
use Laravel\Ai\Streaming\Events\TextEnd;
use Laravel\Ai\Streaming\Events\TextStart;
use Laravel\Ai\Streaming\Events\ToolCall as ToolCallEvent;
use Laravel\Ai\Tools\ToolNameResolver;
use RuntimeException;

use function Laravel\Ai\generate_fake_data_for_json_schema_type;
use function Laravel\Ai\ulid;

/**
 * Answers prompts from a script, so the workbench’s AI features can be tried without a model:
 *
 * - `tools` lists the tools the agent has.
 * - `call <tool> {"arg": "value"}` calls a tool, and the next step replies with what it returned.
 * - `fail` throws an error, as a provider would.
 * - Agents with a single tool, such as Craft’s tool calling check, have it called.
 * - Agents with structured output get fake data for their schema.
 * - Chat titles are the first words of the message.
 * - Anything else is echoed back as Markdown, after some reasoning.
 *
 * Streamed replies are sent a word at a time, with a delay, so they look like a model’s.
 */
class WorkbenchTextGateway implements StepTextGateway
{
    public function __construct(private readonly int $delayMs = 20) {}

    public function generateTextStep(
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        StepContext $stepContext,
    ): StepResponse {
        return $this->respond($provider, $model, $instructions, $messages, $tools, $schema);
    }

    public function generateStreamStep(
        string $invocationId,
        TextProvider $provider,
        string $model,
        ?string $instructions,
        array $messages,
        array $tools,
        ?array $schema,
        ?TextGenerationOptions $options,
        ?int $timeout,
        StepContext $stepContext,
    ): Generator {
        $step = $this->respond($provider, $model, $instructions, $messages, $tools, $schema);

        yield new StreamStart(ulid(), $provider->name(), $model, time())->withInvocationId($invocationId);

        if ($step->reasoning !== '') {
            $reasoningId = ulid();

            yield new ReasoningStart(ulid(), $reasoningId, time())->withInvocationId($invocationId);

            foreach ($this->words($step->reasoning) as $word) {
                yield new ReasoningDelta(ulid(), $reasoningId, $word, time())->withInvocationId($invocationId);
            }

            yield new ReasoningEnd(ulid(), $reasoningId, time())->withInvocationId($invocationId);
        }

        if ($step->text !== '') {
            $messageId = ulid();

            yield new TextStart(ulid(), $messageId, time())->withInvocationId($invocationId);

            foreach ($this->words($step->text) as $word) {
                yield new TextDelta(ulid(), $messageId, $word, time())->withInvocationId($invocationId);
            }

            yield new TextEnd(ulid(), $messageId, time())->withInvocationId($invocationId);
        }

        foreach ($step->toolCalls as $toolCall) {
            yield new ToolCallEvent(ulid(), $toolCall, time())->withInvocationId($invocationId);
        }

        return $step;
    }

    /**
     * @param  Message[]  $messages
     * @param  Tool[]  $tools
     * @param  array<string, Type>|null  $schema
     */
    private function respond(TextProvider $provider, string $model, ?string $instructions, array $messages, array $tools, ?array $schema): StepResponse
    {
        $meta = new Meta($provider->name(), $model);
        $text = static fn (string $text, string $reasoning = ''): StepResponse => new StepResponse($text, [], FinishReason::Stop, new TextUsage, $meta, reasoning: $reasoning);
        $call = static fn (string $name, array $arguments): StepResponse => new StepResponse('', [new ToolCall('call_'.Str::random(12), $name, $arguments)], FinishReason::ToolCalls, new TextUsage, $meta);

        $last = end($messages);

        if ($last instanceof ToolResultMessage) {
            return $text($last->toolResults->map($this->describeToolResult(...))->implode("\n\n"));
        }

        $prompt = '';

        foreach (array_reverse($messages) as $message) {
            if ($message instanceof UserMessage) {
                // Craft’s chat starts messages with what they were sent with.
                $prompt = trim((string) preg_replace('/^\s*<context>.*?<\/context>/s', '', (string) $message->content));
                break;
            }
        }

        $toolNames = array_map(ToolNameResolver::resolve(...), $tools);

        if (strcasecmp($prompt, 'fail') === 0) {
            throw new RuntimeException('The workbench AI provider failed, as you asked it to.');
        }

        if (strcasecmp($prompt, 'tools') === 0) {
            if ($tools === []) {
                return $text('I don’t have any tools.');
            }

            return $text("I have these tools. Call one with `call <tool> {\"arg\": \"value\"}`.\n\n".implode("\n", array_map(
                static fn (Tool $tool, string $name): string => "- `{$name}`: ".Str::limit((string) $tool->description(), 140),
                $tools,
                $toolNames,
            )));
        }

        if (count($tools) === 1) {
            return $call($toolNames[0], []);
        }

        if (preg_match('/^call\s+(\S+)\s*(.*)$/is', $prompt, $match)) {
            if (! in_array($match[1], $toolNames, true)) {
                return $text("I don’t have a `{$match[1]}` tool. Send `tools` to list the ones I have.");
            }

            $arguments = trim($match[2]) === '' ? [] : json_decode(trim($match[2]), true);

            if (! is_array($arguments)) {
                return $text("The arguments for `{$match[1]}` aren’t a JSON object.");
            }

            return $call($match[1], $arguments);
        }

        if ($schema !== null) {
            $data = generate_fake_data_for_json_schema_type(new ObjectType($schema));

            return new StepResponse(Json::encode($data), [], FinishReason::Stop, new TextUsage, $meta, structured: $data);
        }

        if ($tools === [] && str_contains((string) $instructions, 'title')) {
            return $text(Str::words($prompt, 5, ''));
        }

        return $text(
            "You said:\n\n> ".str_replace("\n", "\n> ", $prompt)."\n\nI’m the workbench’s scripted AI provider, so I can’t answer for real. Send `tools` to list my tools, `call <tool> {\"arg\": \"value\"}` to call one, or `fail` to see an error.",
            'Reading the message. It isn’t a command, so I’ll echo it back.',
        );
    }

    private function describeToolResult(ToolResult $result): string
    {
        if ($result->denied) {
            return "You rejected `{$result->name}`.";
        }

        $output = is_string($result->result) ? $result->result : Json::encode($result->result);

        return ($result->failed ? "`{$result->name}` failed:" : "`{$result->name}` returned:")
            ."\n\n```\n".Str::limit($output, 2000)."\n```";
    }

    /**
     * Splits text into words, keeping the whitespace before each one, and waits before returning each, for at most
     * two seconds in all.
     *
     * @return Generator<int, string>
     */
    private function words(string $text): Generator
    {
        $words = preg_split('/(?=\s+\S)/', $text) ?: [];
        $delay = min($this->delayMs * 1000, intdiv(2_000_000, max(count($words), 1)));

        foreach ($words as $word) {
            Sleep::usleep($delay);

            yield $word;
        }
    }
}
