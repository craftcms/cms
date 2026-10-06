<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Responses;

use CraftCms\Cms\Auth\AuthenticationViews;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Laravel\Passport\Client;
use Laravel\Passport\Scope;
use Symfony\Component\HttpFoundation\Response;

/**
 * @since 6.0.0
 */
readonly class AuthorizationView
{
    public function __construct(
        private AuthenticationViews $views,
    ) {}

    /**
     * @param array{
     *     client: Client,
     *     scopes: list<Scope>,
     *     request: Request,
     *     authToken: string,
     * } $parameters
     */
    public function __invoke(array $parameters): Response
    {
        $request = $parameters['request'];
        $clientName = (string) $parameters['client']->getAttribute('name');
        $scopes = array_map(
            static fn (Scope $scope): array => [
                'id' => $scope->id,
                'description' => $scope->description,
            ],
            $parameters['scopes'],
        );
        $authToken = $parameters['authToken'];
        $csrfToken = csrf_token();
        $routePrefix = $request->routeIs('craft.cp.mcp.oauth.*')
            ? 'craft.cp.mcp.oauth.'
            : 'passport.authorizations.';
        $approveAction = route($routePrefix.'approve');
        $denyAction = route($routePrefix.'deny');
        $viewData = compact('clientName', 'scopes', 'authToken', 'csrfToken', 'approveAction', 'denyAction');

        $response = $this->views->render(
            request: $request,
            inertiaComponent: 'auth/McpAuthorization',
            inertiaProps: $viewData,
            templateData: $viewData,
        );

        return Router::toResponse($request, $response);
    }
}
