<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use Closure;
use CraftCms\Cms\Support\Json;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\JsonSchema as JsonSchemaFactory;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Schema\SchemaNormalizer;
use Laravel\Ai\Tools\Request;
use Mcp\Schema\Content\AudioContent;
use Mcp\Schema\Content\Content;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\ResourceLink;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Schema\Tool as ToolDefinition;

use function CraftCms\Cms\t;

/**
 * An MCP tool, as a `laravel/ai` tool for the AI assistant.
 *
 * Tools that can change anything only run once the user approves the call. Calls go to wherever the tool is, such as
 * {@see McpTools} for Craft’s own tools, or {@see CraftcmsMcpTools} for craftcms.com’s.
 *
 * @internal
 *
 * @since 6.0.0
 */
class McpTool implements Approvable, Tool
{
    use InteractsWithApprovals;

    /**
     * @param  string  $name  The tool’s name for providers, which only allow letters, numbers, underscores, and hyphens.
     * @param  Closure(array<string, mixed>): string  $call  Calls the tool with its arguments, and returns its result as text for the model.
     */
    public function __construct(
        private readonly string $name,
        private readonly ToolDefinition $definition,
        private readonly Closure $call,
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
        return ($this->call)($request->all());
    }

    /**
     * Returns a tool result as text for the model.
     *
     * Structured content comes first, as JSON, since tools that return it repeat it in their content blocks. Content
     * the model can’t read as text, such as images, is described instead.
     */
    public static function resultText(CallToolResult $result): string
    {
        if ($result->structuredContent !== null) {
            return (string) Json::encode($result->structuredContent, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return implode("\n", array_map(static fn (Content $content): string => match (true) {
            $content instanceof TextContent => (string) $content->text,
            $content instanceof EmbeddedResource && $content->resource instanceof TextResourceContents => $content->resource->text,
            $content instanceof EmbeddedResource => "[Binary resource: {$content->resource->uri}]",
            $content instanceof ResourceLink => "[Resource: {$content->name} ({$content->uri})]",
            $content instanceof ImageContent => "[Image: {$content->mimeType}]",
            $content instanceof AudioContent => "[Audio: {$content->mimeType}]",
            default => '',
        }, $result->content));
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
