<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchema as JsonSchemaFactory;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Schema\SchemaNormalizer;
use Laravel\Ai\Tools\Request;

/**
 * The tool the model submits its answer with: the fields it changes, and what it noticed.
 *
 * The answer is a tool call rather than structured output so the model’s summary, which it writes before calling it,
 * can be streamed. {@see Assistant::ask()} reads the call’s arguments from the stream.
 *
 * @internal
 *
 * @since 6.0.0
 */
readonly class ProposeChanges implements Tool
{
    /**
     * What the tool returns, which the model only sees when the call is replayed in a later turn.
     */
    public const string RESULT = 'Received.';

    /**
     * The tool’s name, which the instructions refer to.
     */
    public const string NAME = 'propose_changes';

    /**
     * @param  array<string, array<string, mixed>>  $properties  JSON schemas of the writable fields, indexed by their keys.
     * @param  list<string>  $findingFields  The keys of every field a finding can concern, writable or not.
     */
    public function __construct(
        private array $properties,
        private array $findingFields,
    ) {}

    public function name(): string
    {
        return self::NAME;
    }

    public function description(): string
    {
        return 'Submits your answer: the new content of the fields you change, and the problems you noticed but didn’t fix. Call it exactly once, after your summary.';
    }

    public function handle(Request $request): string
    {
        return self::RESULT;
    }

    /**
     * Findings come first, so the model settles what it noticed, and whether to change anything, before it writes
     * values.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'findings' => $schema->array()
                ->description('Specific problems you noticed, one per item.')
                ->items($schema->object([
                    // `anyOf` rather than a nullable enum, which providers such as Anthropic reject.
                    'field' => JsonSchemaFactory::fromArray(SchemaNormalizer::normalize(['anyOf' => [
                        ['type' => 'string', 'enum' => $this->findingFields],
                        ['type' => 'null'],
                    ]]))
                        ->description('The field the problem concerns, or null for the content as a whole.')
                        ->required(),
                    'quote' => $schema->string()
                        ->nullable()
                        ->description('The exact passage the problem is in, copied character for character from the field’s current content, or null when it isn’t about a specific passage.')
                        ->required(),
                    'issue' => $schema->string()
                        ->description('The problem, and how to fix it.')
                        ->required(),
                ])->withoutAdditionalProperties())
                ->required(),
            'values' => $schema->object(array_map(
                static fn (array $property): Type => JsonSchemaFactory::fromArray(SchemaNormalizer::normalize($property))->required(),
                $this->properties,
            ))
                ->description('Each field’s new content, exactly as it should be stored, or null to leave the field unchanged. Never put notes, comments, or explanations here.')
                ->withoutAdditionalProperties()
                ->required(),
        ];
    }
}
