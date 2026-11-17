<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Cms;
use CraftCms\Cms\Mcp\Settings;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Advertises the discoverable MCP servers, per SEP-2127.
 *
 * @see https://modelcontextprotocol.io/seps/2127-mcp-server-cards
 * @since 6.0.0
 */
readonly class DiscoveryController
{
    public function __construct(
        private Settings $settings,
    ) {}

    /** Lists the discoverable servers in an AI Catalog, for `/.well-known/ai-catalog.json`. */
    public function catalog(Request $request): Response
    {
        $entries = [];

        foreach ($this->settings->discoverableServers() as $path => $isPublic) {
            $entries[] = [
                'identifier' => sprintf('urn:air:%s:mcp:%s', parse_url($this->settings->url($path), PHP_URL_HOST), $this->settings->serverName($isPublic)),
                'type' => 'application/mcp-server-card+json',
                'url' => $this->settings->url("$path/server-card"),
            ];
        }

        abort_if($entries === [], 404);

        return $this->respond($request, 'application/ai-catalog+json', ['specVersion' => '1.0', 'entries' => $entries]);
    }

    /** Returns a discoverable server's Server Card. */
    public function serverCard(Request $request): Response
    {
        $endpoint = (string) $request->route('endpoint');
        $isPublic = $this->settings->discoverableServers()[$endpoint] ?? abort(404);
        $url = $this->settings->url($endpoint);

        return $this->respond($request, 'application/mcp-server-card+json', [
            '$schema' => 'https://static.modelcontextprotocol.io/schemas/v1/server-card.schema.json',
            'name' => implode('.', array_reverse(explode('.', (string) parse_url($url, PHP_URL_HOST)))).'/'.$this->settings->serverName($isPublic),
            // Only the major version, so the card doesn't publicly reveal the exact Craft version
            'version' => (string) (int) Cms::version(),
            'description' => 'Craft CMS MCP server',
            'title' => Str::limit(Cms::systemName(), 100, ''),
            'remotes' => [['type' => 'streamable-http', 'url' => $url]],
        ]);
    }

    /**
     * Returns public discovery metadata with the CORS and caching headers SEP-2127 calls for.
     *
     * @param  array<string, mixed>  $data
     */
    private function respond(Request $request, string $contentType, array $data): Response
    {
        $response = new Response(status: $request->isMethod('OPTIONS') ? 204 : 200, headers: [
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET',
            'Access-Control-Allow-Headers' => 'Content-Type, If-None-Match',
            'Access-Control-Expose-Headers' => 'ETag',
            'Cache-Control' => 'public, max-age=3600',
        ]);

        if ($request->isMethod('OPTIONS')) {
            return $response;
        }

        $json = (string) Json::encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $response->setContent($json);
        $response->headers->set('Content-Type', $contentType);
        $response->setEtag(sha1($json));
        $response->isNotModified($request);

        return $response;
    }
}
