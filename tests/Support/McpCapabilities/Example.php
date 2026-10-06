<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\Support\McpCapabilities;

use CraftCms\Cms\Mcp\Attributes\PublicMcp;
use CraftCms\Cms\Mcp\Attributes\RequiresPermission;
use CraftCms\Cms\Mcp\Settings;
use Mcp\Capability\Attribute\McpPrompt;
use Mcp\Capability\Attribute\McpResource;
use Mcp\Capability\Attribute\McpResourceTemplate;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Schema\Content\PromptMessage;
use Mcp\Schema\Content\TextContent;
use Mcp\Schema\Enum\Role;

readonly class Example
{
    public function __construct(private Settings $settings) {}

    /** @return array{endpoint: string, greeting: string} */
    #[McpTool(name: 'example.greet', title: 'Greet a visitor', description: 'Returns a greeting for the supplied name.')]
    #[PublicMcp]
    public function greet(string $name): array
    {
        return ['endpoint' => $this->settings->publicRoute, 'greeting' => "Hello, $name!"];
    }

    #[McpTool(name: 'example.manage')]
    #[RequiresPermission('manageExample')]
    public function manage(): string
    {
        return 'Managed example';
    }

    #[McpResource(uri: 'example://welcome', name: 'example-welcome', title: 'Welcome message')]
    #[PublicMcp]
    public function welcome(): string
    {
        return 'Welcome to the example plugin';
    }

    #[McpResourceTemplate(uriTemplate: 'example://visitors/{name}', name: 'example-visitor', title: 'Visitor greeting')]
    #[PublicMcp]
    public function visitor(string $name): string
    {
        return "Welcome, $name";
    }

    /** @return list<PromptMessage> */
    #[McpPrompt(name: 'example-introduce', title: 'Introduce a visitor')]
    #[PublicMcp]
    public function introduce(string $name): array
    {
        return [new PromptMessage(Role::User, new TextContent("Introduce $name"))];
    }
}
