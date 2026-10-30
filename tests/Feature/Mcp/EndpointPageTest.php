<?php

declare(strict_types=1);

use CraftCms\Cms\Mcp\RouteRegistrar;
use CraftCms\Cms\Mcp\Settings;
use Inertia\Testing\AssertableInertia;

it('shows connection instructions when a browser opens the admin endpoint', function (): void {
    $this->get(route('craft.mcp.server'), ['Accept' => 'text/html,application/xhtml+xml'])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/McpEndpoint')
            ->where('url', route('craft.mcp.authorization'))
            ->where('isPublic', false));
});

it('shows connection instructions for the public endpoint only while it is enabled', function (): void {
    $route = '/_test/public-mcp';

    $this->get($route, ['Accept' => 'text/html'])->assertNotFound();

    app()->instance(Settings::class, new Settings(['publicEnabled' => true, 'publicRoute' => $route]));
    app(RouteRegistrar::class)->register();

    $this->get($route, ['Accept' => 'text/html'])
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('auth/McpEndpoint')
            ->where('url', url($route))
            ->where('isPublic', true));
});

it('rejects GET requests from MCP clients', function (): void {
    $route = '/_test/public-mcp';
    app()->instance(Settings::class, new Settings(['publicEnabled' => true, 'publicRoute' => $route]));
    app(RouteRegistrar::class)->register();

    $this->get(route('craft.mcp.server'), ['Accept' => 'text/event-stream'])->assertMethodNotAllowed();
    $this->get($route, ['Accept' => 'text/event-stream'])->assertMethodNotAllowed();
});
