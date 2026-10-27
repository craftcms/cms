<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Activity\ActivityEventRecorder;
use CraftCms\Cms\Mcp\AdminInstructions;
use CraftCms\Cms\Mcp\CapabilityContainer;
use CraftCms\Cms\Mcp\CapabilityDiscovery;
use CraftCms\Cms\Support\Json;
use Illuminate\Support\Facades\Context;
use Mcp\Capability\Registry;
use Mcp\Capability\Registry\ReferenceHandler;
use Mcp\Capability\Registry\ToolReference;
use Mcp\Schema\Content\AudioContent;
use Mcp\Schema\Content\Content;
use Mcp\Schema\Content\EmbeddedResource;
use Mcp\Schema\Content\ImageContent;
use Mcp\Schema\Content\ResourceLink;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Content\TextResourceContents;
use Mcp\Schema\JsonRpc\Error;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\Result\CallToolResult;
use Mcp\Server\Handler\Request\CallToolHandler;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;

/**
 * Craft’s MCP tools, for the AI assistant to call in-process as the current user.
 *
 * Tools are discovered as the stdio MCP server discovers them: the same edition, admin, and permission requirements
 * apply, with no access token to scope them and none of the tools that only work over HTTP. Each tool still checks the
 * user’s permissions for the records it touches. Tools that can change anything ask the user to approve each call, as
 * {@see McpTool} does. Changes are recorded as the AI assistant’s, in their activity.
 *
 * Tool names are renamed for providers, which only allow letters, numbers, underscores, and hyphens: `elements.get`
 * becomes `elements_get`.
 *
 * @internal
 *
 * @since 6.0.0
 */
class McpTools
{
    /** @var array<string, ToolReference>|null The tools, indexed by their provider names. */
    private ?array $references = null;

    private ?CallToolHandler $handler = null;

    public function __construct(
        private readonly CapabilityDiscovery $discovery,
        private readonly CapabilityContainer $container,
        private readonly AdminInstructions $instructions,
    ) {}

    /**
     * @return list<McpTool>
     */
    public function tools(): array
    {
        $tools = [];

        foreach ($this->references() as $name => $reference) {
            $tools[] = new McpTool($this, $name, $reference->tool);
        }

        return $tools;
    }

    /**
     * Returns the admin MCP server’s instructions, with tool names as the assistant knows them.
     */
    public function instructions(): string
    {
        $names = [];

        foreach ($this->references() as $name => $reference) {
            $names[$reference->tool->name] = $name;
        }

        return strtr($this->instructions->get(), $names);
    }

    /**
     * Calls a tool by its provider name, and returns its result as text for the model.
     *
     * Errors, including invalid arguments and denied permissions, are returned as text too, so the model can correct
     * itself.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function call(string $name, array $arguments): string
    {
        $reference = $this->references()[$name] ?? null;

        if ($reference === null) {
            return "Error: There’s no tool called {$name}.";
        }

        $response = Context::scope(
            fn () => $this->handler()->handle(
                new CallToolRequest($reference->tool->name, $arguments)->withId(1),
                new Session(new InMemorySessionStore),
            ),
            hidden: [ActivityEventRecorder::ContextOrigin => 'AI assistant'],
        );

        if ($response instanceof Error) {
            return "Error: {$response->message}";
        }

        $result = $response->result;
        assert($result instanceof CallToolResult);

        return $result->isError ? "Error: {$this->text($result)}" : $this->text($result);
    }

    /**
     * Returns a tool result as text for the model.
     *
     * Structured content comes first, as JSON, since tools that return it repeat it in their content blocks. Content
     * the model can’t read as text, such as images, is described instead.
     */
    private function text(CallToolResult $result): string
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

    /**
     * @return array<string, ToolReference>
     */
    private function references(): array
    {
        if ($this->references !== null) {
            return $this->references;
        }

        $this->references = [];
        $discovered = $this->discovery->discoverWithoutToken(dirname(__DIR__, 2).'/Mcp/Capabilities', ['.']);

        foreach ($discovered->getTools() as $reference) {
            $this->references[str_replace('.', '_', $reference->tool->name)] = $reference;
        }

        ksort($this->references);

        return $this->references;
    }

    private function handler(): CallToolHandler
    {
        if ($this->handler !== null) {
            return $this->handler;
        }

        $registry = new Registry;

        foreach ($this->references() as $reference) {
            $registry->registerTool($reference->tool, $reference->handler);
        }

        return $this->handler = new CallToolHandler($registry, new ReferenceHandler($this->container));
    }
}
