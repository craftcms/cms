<?php

namespace craft\mcp\capabilities;

use Craft;
use craft\mcp\attributes\RequiresAdmin;
use craft\mcp\attributes\RequiresAdminChanges;
use craft\services\Routes as RouteService;
use Illuminate\Support\Arr;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Server\RequestContext;

/** @since 5.12.0 */
class Routes
{
    private const UriPartSchema = [
        'anyOf' => [
            ['type' => 'string'],
            ['type' => 'array', 'items' => ['type' => 'string'], 'minItems' => 2, 'maxItems' => 2],
        ],
    ];

    public function __construct(private RouteService $routes)
    {
    }

    /** @return array{count: int, routes: list<array<string, mixed>>} */
    #[RequiresAdmin]
    public function list(): array
    {
        $routes = $this->records();

        return ['count' => count($routes), 'routes' => $routes];
    }

    /** @return array{route: array<string, mixed>} */
    #[RequiresAdmin]
    public function get(#[Schema(format: 'uuid')] ?string $uid = null, ?string $uri = null): array
    {
        return ['route' => $this->find($uid, $uri)];
    }

    /**
     * @param list<string|array{0: string, 1: string}> $uriParts Literal strings and [name, regex] parameter tuples.
     * @return array{route: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function create(
        #[Schema(items: self::UriPartSchema)] array $uriParts,
        string $template,
        #[Schema(format: 'uuid')] ?string $siteUid = null,
    ): array {
        $uid = $this->routes->saveRoute($uriParts, $template, $siteUid);

        return ['route' => $this->saved($uid)];
    }

    /**
     * @param list<string|array{0: string, 1: string}> $uriParts Literal strings and [name, regex] parameter tuples.
     * @return array{route: array<string, mixed>}
     */
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        #[Schema(format: 'uuid')] ?string $uid = null,
        ?string $uri = null,
        #[Schema(items: self::UriPartSchema)] array $uriParts = [],
        string $template = '',
        #[Schema(format: 'uuid')] ?string $siteUid = null,
    ): array {
        $route = $this->find($uid, $uri);
        $request = $context->getRequest();
        assert($request instanceof CallToolRequest);

        $this->routes->saveRoute(
            Arr::get($request->arguments, 'uriParts', $route['uriParts']),
            Arr::get($request->arguments, 'template', $route['template']),
            Arr::get($request->arguments, 'siteUid', $route['siteUid']),
            $route['uid'],
        );

        return ['route' => $this->saved($route['uid'])];
    }

    /** @return array{deleted: true} */
    #[RequiresAdminChanges]
    public function delete(#[Schema(format: 'uuid')] ?string $uid = null, ?string $uri = null): array
    {
        $route = $this->find($uid, $uri);

        if (!$this->routes->deleteRouteByUid($route['uid'])) {
            throw new ToolCallException('Route could not be deleted.');
        }

        return ['deleted' => true];
    }

    /** @return array<string, mixed> */
    private function find(?string $uid, ?string $uri): array
    {
        if (($uid === null) === ($uri === null)) {
            throw new ToolCallException('Provide exactly one of: uid, uri.');
        }

        foreach ($this->records() as $route) {
            if ($uid !== null ? $route['uid'] === $uid : $route['uri'] === $uri) {
                return $route;
            }
        }

        throw new ToolCallException('Route not found.');
    }

    /** @return array<string, mixed> */
    private function saved(string $uid): array
    {
        $route = Craft::$app->getProjectConfig()->get("routes.$uid");
        $uri = implode('', array_map(
            static fn(string|array $part): string => is_array($part) ? "{{$part[0]}}" : $part,
            $route['uriParts'],
        ));

        return ['uid' => $uid, 'uri' => $uri, ...$route];
    }

    /** @return list<array<string, mixed>> */
    private function records(): array
    {
        $routes = Craft::$app->getProjectConfig()->get('routes') ?? [];
        $siteUid = Craft::$app->getSites()->getCurrentSite()->uid;
        $records = [];

        foreach ($routes as $uid => $route) {
            if (!empty($route['siteUid']) && $route['siteUid'] !== $siteUid) {
                continue;
            }

            $records[] = $this->saved($uid);
        }

        usort($records, static fn(array $a, array $b): int => ($a['sortOrder'] ?? 0) <=> ($b['sortOrder'] ?? 0));

        return $records;
    }
}
