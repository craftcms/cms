<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\AskAi;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchema as JsonSchemaFactory;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Laravel\Ai\Schema\SchemaNormalizer;

/**
 * Returns new values for the fields an editor may change, with a summary and findings, as structured output.
 *
 * @internal
 *
 * @since 6.0.0
 */
class AssistantAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  array<string, array<string, mixed>>  $properties  JSON schemas of the writable fields, indexed by their keys.
     * @param  list<string>  $findingFields  The keys of every field a finding can concern, writable or not.
     */
    public function __construct(
        private readonly string $instructions,
        private readonly array $properties,
        private readonly array $findingFields,
    ) {}

    public function instructions(): string
    {
        return $this->instructions;
    }

    /**
     * Findings and the summary come first, so the model settles what it noticed, and whether to change anything,
     * before it writes values.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'findings' => $schema->array()
                ->description('Specific problems you noticed, one per item.')
                ->items($schema->object([
                    'field' => $schema->string()
                        ->enum([...$this->findingFields, null])
                        ->nullable()
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
            'summary' => $schema->string()
                ->nullable()
                ->description('A short explanation of what you changed, or your review when you change nothing.')
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
