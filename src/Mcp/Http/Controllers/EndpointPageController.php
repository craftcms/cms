<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Auth\AuthenticationViews;
use CraftCms\Cms\Cms;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Explains an MCP endpoint to people who open it in a browser.
 *
 * MCP clients that send a GET for an event stream still get the 405 the stateless transport requires.
 *
 * @since 6.0.0
 */
readonly class EndpointPageController
{
    public function __construct(
        private AuthenticationViews $views,
    ) {}

    public function admin(Request $request): View|InertiaResponse|Response
    {
        return $this->render($request, $this->serverName().'-admin', false);
    }

    public function public(Request $request): View|InertiaResponse|Response
    {
        return $this->render($request, $this->serverName(), true);
    }

    private function serverName(): string
    {
        return Str::slug(Cms::systemName()) ?: 'craft';
    }

    private function render(Request $request, string $serverName, bool $isPublic): View|InertiaResponse|Response
    {
        if ($request->prefers(['text/html', 'text/event-stream', 'application/json']) !== 'text/html') {
            throw new MethodNotAllowedHttpException(['POST', 'OPTIONS']);
        }

        $viewData = [
            'url' => $request->url(),
            'serverName' => $serverName,
            'isPublic' => $isPublic,
        ];

        return $this->views->render(
            request: $request,
            inertiaComponent: 'auth/McpEndpoint',
            templateData: $viewData,
        );
    }
}
