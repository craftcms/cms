<?php

declare(strict_types=1);

namespace CraftCms\Cms\Ai\Assistant;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Support\Json;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mcp\Client;
use Mcp\Client\Transport\HttpTransport;
use Mcp\Schema\Tool as ToolDefinition;
use Throwable;

/**
 * The tools of craftcms.com’s MCP server, which searches Craft’s documentation, Knowledge Base, and glossary, for the AI
 * assistant to answer questions about how Craft works.
 *
 * The tool list is cached for a day, so turns don’t wait on craftcms.com; the assistant connects only to call a tool.
 * When craftcms.com can’t be reached, the assistant goes without its tools for a few minutes, rather than failing.
 *
 * Tool names are prefixed with `craftcms_`, and renamed for providers: `docs-search` becomes `craftcms_docs_search`.
 *
 * @internal
 *
 * @since 6.0.0
 */
class CraftcmsMcpTools
{
    public const string ENDPOINT = 'https://craftcms.com/mcp';

    private const string CACHE_KEY = 'craftcms-mcp-tools';

    private ?Client $client = null;

    /**
     * @return list<McpTool>
     */
    public function tools(): array
    {
        $tools = [];

        foreach ($this->definitions() as $definition) {
            $name = 'craftcms_'.str_replace(['-', '.'], '_', $definition->name);
            $tools[] = new McpTool($name, $definition, fn (array $arguments): string => $this->call($definition->name, $arguments));
        }

        return $tools;
    }

    /**
     * Calls a tool by its name on craftcms.com, and returns its result as text for the model.
     *
     * Errors, including craftcms.com being unreachable, are returned as text too, so the model can say so.
     *
     * @param  array<string, mixed>  $arguments
     */
    public function call(string $name, array $arguments): string
    {
        try {
            $result = $this->client()->callTool($name, $arguments);
        } catch (Throwable $exception) {
            return "Error: craftcms.com couldn’t be reached: {$exception->getMessage()}";
        }

        return $result->isError ? 'Error: '.McpTool::resultText($result) : McpTool::resultText($result);
    }

    /**
     * @return list<ToolDefinition>
     */
    private function definitions(): array
    {
        $definitions = Cache::get(self::CACHE_KEY);

        if (! is_array($definitions)) {
            try {
                $definitions = Json::decode((string) Json::encode($this->client()->listTools()->tools));
                Cache::put(self::CACHE_KEY, $definitions, now()->addDay());
            } catch (Throwable $exception) {
                Log::warning("The AI assistant couldn’t load craftcms.com’s MCP tools: {$exception->getMessage()}");
                $definitions = [];
                Cache::put(self::CACHE_KEY, $definitions, now()->addMinutes(5));
            }
        }

        return array_map(ToolDefinition::fromArray(...), $definitions);
    }

    /**
     * Connects to craftcms.com through Laravel’s HTTP client, so requests can be faked in tests.
     */
    private function client(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $client = Client::builder()
            ->setClientInfo('Craft CMS', Cms::version())
            ->setInitTimeout(10)
            ->setRequestTimeout(20)
            ->build();

        $client->connect(new HttpTransport(self::ENDPOINT, httpClient: new GuzzleClient([
            'handler' => Http::buildHandlerStack(),
            'connect_timeout' => 5,
            'timeout' => 20,
        ])));

        return $this->client = $client;
    }
}
