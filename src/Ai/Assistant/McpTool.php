<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchema as JsonSchemaFactory;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Schema\SchemaNormalizer;
use Laravel\Ai\Tools\Request;
use Mcp\Schema\Tool as ToolDefinition;

use function CraftCms\Cms\t;

/**
 * One of Craft’s MCP tools, as a `laravel/ai` tool for the AI assistant.
 *
 * Tools that can change anything only run once the user approves the call.
 *
 * @internal
 *
 * @since 6.0.0
 */
class McpTool implements Approvable, Tool
{
    use InteractsWithApprovals;

    /**
     * @param  string  $name  The tool’s name for providers, from {@see McpTools}.
     */
    public function __construct(
        private readonly McpTools $tools,
        private readonly string $name,
        private readonly ToolDefinition $definition,
    ) {}

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->definition->description ?? $this->definition->title ?? $this->definition->name;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        $required = $this->definition->inputSchema['required'] ?? [];
        $types = [];

        foreach ((array) ($this->definition->inputSchema['properties'] ?? []) as $key => $property) {
            $type = JsonSchemaFactory::fromArray(SchemaNormalizer::normalize((array) $property));
            $types[(string) $key] = in_array($key, $required, true) ? $type->required() : $type;
        }

        return $types;
    }

    public function handle(Request $request): string
    {
        return $this->tools->call($this->name, $request->all());
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $annotations = $this->definition->annotations;

        if ($annotations?->readOnlyHint === true) {
            return false;
        }

        return Approval::required($annotations?->destructiveHint === false
            ? t('This changes content or settings.')
            : t('This can delete or overwrite content or settings.'));
    }
}
