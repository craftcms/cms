<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;

/**
 * Asks a model for a word and a number in a JSON schema, to check that it can return structured output.
 *
 * @internal
 *
 * @since 6.0.0
 */
class StructuredOutputProbe implements Agent, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return 'You check whether you can return structured output. Return exactly the values you’re asked for.';
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'word' => $schema->string()->required(),
            'number' => $schema->integer()->required(),
        ];
    }
}
