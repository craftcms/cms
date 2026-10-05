<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Responses;

use CraftCms\Cms\Auth\AuthenticationViews;
use Illuminate\Http\Request;
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
        $approveAction = route('passport.authorizations.approve');
        $denyAction = route('passport.authorizations.deny');
        $viewData = compact('clientName', 'scopes', 'authToken', 'csrfToken', 'approveAction', 'denyAction');

        return $this->views->render(
            request: $request,
            inertiaComponent: 'auth/McpAuthorization',
            inertiaProps: $viewData,
            templateData: $viewData,
        );
    }
}
