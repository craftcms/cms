<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Capabilities;

use CraftCms\Cms\Mcp\Attributes\RequiresAdmin;
use CraftCms\Cms\Mcp\Attributes\RequiresAdminChanges;
use CraftCms\Cms\Route\Data\Route;
use CraftCms\Cms\Route\Exceptions\InvalidRouteException;
use CraftCms\Cms\Route\Routes as RouteService;
use CraftCms\Cms\Support\Arr;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Exception\ToolCallException;
use Mcp\Schema\Request\CallToolRequest;
use Mcp\Schema\ToolAnnotations;
use Mcp\Server\RequestContext;

/**
 * @since 6.0.0
 */
readonly class Routes
{
    private const array UriPartSchema = [
        'anyOf' => [
            ['type' => 'string'],
            [
                'type' => 'array',
                'items' => ['type' => 'string'],
                'minItems' => 2,
                'maxItems' => 2,
            ],
        ],
    ];

    public function __construct(private RouteService $routes) {}

    /** @return array{routes: list<array<string, mixed>>} */
    #[McpTool(
        name: 'routes.list',
        description: 'Lists Craft CMS routes available to the current site.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function list(): array
    {
        return [
            'routes' => $this->routes
                ->getProjectConfigRoutes()
                ->map($this->serialize(...))
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  string|null  $uid  Route UID.
     * @param  string|null  $uri  Computed route URI, such as blog/{slug}.
     * @return array{route: array<string, mixed>}
     */
    #[McpTool(
        name: 'routes.get',
        description: 'Gets a Craft CMS route by UID or URI.',
        annotations: new ToolAnnotations(readOnlyHint: true),
    )]
    #[RequiresAdmin]
    public function get(
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $uri = null,
    ): array {
        if (count(array_filter([$uid, $uri], static fn (mixed $value): bool => $value !== null)) !== 1) {
            throw new ToolCallException('Provide exactly one of: uid, uri.');
        }

        $route = $this->find($uid, $uri);

        if (! $route) {
            throw new ToolCallException('Route not found.');
        }

        return ['route' => $this->serialize($route)];
    }

    /**
     * @param  list<string|array{0: string, 1: string}>  $uriParts  Literal strings and [name, regex] parameter tuples.
     * @param  string  $template  Template path this route should render.
     * @param  string|null  $siteUid  Optional site UID this route is limited to.
     * @return array{route: array<string, mixed>}
     */
    #[McpTool(name: 'routes.create', description: 'Creates a Craft CMS route.')]
    #[RequiresAdminChanges]
    public function create(
        #[Schema(items: self::UriPartSchema)]
        array $uriParts,
        string $template,
        #[Schema(format: 'uuid')]
        ?string $siteUid = null,
    ): array {
        $route = new Route($uriParts, $template, $siteUid);

        try {
            $route->uid = $this->routes->saveRoute($route);
        } catch (InvalidRouteException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return ['route' => $this->serialize($route)];
    }

    /**
     * @param  string|null  $uid  Route UID.
     * @param  string|null  $uri  Computed route URI, such as blog/{slug}.
     * @param  list<string|array{0: string, 1: string}>  $uriParts  Literal strings and [name, regex] parameter tuples.
     * @param  string  $template  Template path this route should render.
     * @param  string|null  $siteUid  Optional site UID this route is limited to.
     * @return array{route: array<string, mixed>}
     */
    #[McpTool(
        name: 'routes.update',
        description: 'Updates a Craft CMS route.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function update(
        RequestContext $context,
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $uri = null,
        #[Schema(items: self::UriPartSchema)]
        array $uriParts = [],
        string $template = '',
        #[Schema(format: 'uuid')]
        ?string $siteUid = null,
    ): array {
        $route = $this->find($uid, $uri);

        if (! $route) {
            throw new ToolCallException('Route not found.');
        }

        $request = $context->getRequest();

        if (! $request instanceof CallToolRequest) {
            throw new ToolCallException('Invalid route request.');
        }

        $route = new Route(
            uriParts: Arr::get($request->arguments, 'uriParts', $route->uriParts),
            template: Arr::get($request->arguments, 'template', $route->template),
            siteUid: Arr::get($request->arguments, 'siteUid', $route->siteUid),
            uid: $route->uid,
            sortOrder: $route->sortOrder,
        );

        try {
            $route->uid = $this->routes->saveRoute($route);
        } catch (InvalidRouteException $exception) {
            throw new ToolCallException($exception->getMessage(), previous: $exception);
        }

        return ['route' => $this->serialize($route)];
    }

    /**
     * @param  string|null  $uid  Route UID.
     * @param  string|null  $uri  Computed route URI, such as blog/{slug}.
     * @return array{deleted: true}
     */
    #[McpTool(
        name: 'routes.delete',
        description: 'Deletes a Craft CMS route.',
        annotations: new ToolAnnotations(destructiveHint: true),
    )]
    #[RequiresAdminChanges]
    public function delete(
        #[Schema(format: 'uuid')]
        ?string $uid = null,
        ?string $uri = null,
    ): array {
        $route = $this->find($uid, $uri);

        if (! $route?->uid) {
            throw new ToolCallException('Route not found.');
        }

        if (! $this->routes->deleteRouteByUid($route->uid)) {
            throw new ToolCallException('Route could not be deleted.');
        }

        return ['deleted' => true];
    }

    private function find(?string $uid, ?string $uri): ?Route
    {
        $route = $this->routes
            ->getProjectConfigRoutes()
            ->first(static fn (Route $route): bool => $uid !== null
                ? $route->uid === $uid
                : $uri !== null && $route->getUri() === $uri);

        return $route instanceof Route ? $route : null;
    }

    /** @return array<string, mixed> */
    private function serialize(Route $route): array
    {
        return [
            'uid' => $route->uid,
            'uri' => $route->getUri(),
            'sortOrder' => $route->sortOrder,
            ...$route->configData(),
        ];
    }
}
