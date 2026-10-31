<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer\Http\Responses;

use CraftCms\Cms\Auth\AuthenticationViews;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
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
        private OAuthResources $resources,
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
        $client = $parameters['client'];
        $clientName = (string) $client->getAttribute('name');
        $redirectUri = $request->query('redirect_uri') ?? $client->getAttribute('redirect_uris')[0] ?? null;
        $resource = $this->resources->forRequest($request);
        $user = $request->craftUser();
        $accessChoices = $resource && $user
            ? $resource->choices(array_map(static fn (Scope $scope): string => $scope->id, $parameters['scopes']), $user)
            : [];

        $scopes = collect($parameters['scopes'])
            ->reject(static fn (Scope $scope): bool => $accessChoices !== [] && $resource?->owns($scope->id))
            ->map(static fn (Scope $scope): array => [
                'id' => $scope->id,
                'description' => $scope->description,
            ])
            ->values()
            ->all();
        $accessOptions = array_map(static fn (string $scope): array => [
            'value' => $scope,
            ...$resource->levels[$scope],
        ], $accessChoices);

        $authToken = $parameters['authToken'];
        $csrfToken = csrf_token();
        $routePrefix = $resource ? "$resource->routeName." : 'passport.authorizations.';
        $approveAction = route($routePrefix.'approve');
        $denyAction = route($routePrefix.'deny');
        $viewData = compact('clientName', 'redirectUri', 'scopes', 'accessOptions', 'authToken', 'csrfToken', 'approveAction', 'denyAction');

        $response = $this->views->render(
            request: $request,
            inertiaComponent: 'auth/OAuthAuthorization',
            inertiaProps: $viewData,
            templateData: $viewData,
        );

        $response = Router::toResponse($request, $response);
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'", replace: false);

        return $response;
    }
}
