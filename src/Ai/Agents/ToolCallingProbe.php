<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\Request;

/**
 * Gives a model a tool that returns a code only the tool knows, to check that it can call tools.
 *
 * @internal
 *
 * @since 6.0.0
 */
class ToolCallingProbe implements Agent, HasTools
{
    use Promptable;

    public function __construct(private readonly string $code) {}

    public function instructions(): string
    {
        return 'You check whether you can call tools. Call the tool you’re given, then reply with only what it returns.';
    }

    public function tools(): iterable
    {
        return [new readonly class($this->code) implements Tool
        {
            public function __construct(private string $code) {}

            public function description(): string
            {
                return 'Returns the verification code.';
            }

            public function handle(Request $request): string
            {
                return $this->code;
            }

            public function schema(JsonSchema $schema): array
            {
                return [];
            }
        }];
    }
}
