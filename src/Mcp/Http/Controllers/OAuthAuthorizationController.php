<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\Http\Responses\AuthorizationView;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Controllers\AuthorizationController;
use Laravel\Passport\Http\Responses\SimpleViewResponse;
use League\OAuth2\Server\AuthorizationServer;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\craftAuth;

/** @since 6.0.0 */
class OAuthAuthorizationController extends AuthorizationController
{
    public function __construct(
        AuthorizationServer $server,
        ClientRepository $clients,
    ) {
        $guard = craftAuth();

        if (! $guard instanceof StatefulGuard) {
            throw new LogicException('The configured Craft authentication guard must be stateful.');
        }

        parent::__construct(
            server: $server,
            guard: $guard,
            clients: $clients,
        );
    }

    public function __invoke(
        ServerRequestInterface $psrRequest,
        Request $request,
        ResponseInterface $psrResponse,
        AuthorizationView $view,
    ): Response|AuthorizationViewResponse {
        return parent::authorize(
            psrRequest: $psrRequest,
            request: $request,
            psrResponse: $psrResponse,
            viewResponse: new SimpleViewResponse($view(...)),
        );
    }
}
