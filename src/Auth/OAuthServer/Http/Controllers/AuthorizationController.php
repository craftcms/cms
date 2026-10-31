<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer\Http\Controllers;

use CraftCms\Cms\Auth\OAuthServer\Http\Responses\AuthorizationView;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Controllers\AuthorizationController as PassportAuthorizationController;
use Laravel\Passport\Http\Responses\SimpleViewResponse;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\RequestTypes\AuthorizationRequestInterface;
use LogicException;
use Override;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Response;

use function CraftCms\Cms\craftAuth;

/** @since 6.0.0 */
class AuthorizationController extends PassportAuthorizationController
{
    public function __construct(
        AuthorizationServer $server,
        ClientRepository $clients,
        private readonly OAuthResources $resources,
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

    /**
     * Skips the consent screen when the user already approved this client and the request overlaps the access level
     * they chose then.
     */
    #[Override]
    protected function hasGrantedScopes(Authenticatable $user, Client $client, array $scopes): bool
    {
        $granted = $this->grantedScopes($user->getAuthIdentifier(), $client);
        $resource = $this->resources->forRequest(request());

        if ($resource?->highestIn($granted) === null) {
            return parent::hasGrantedScopes($user, $client, $scopes);
        }

        return $resource->grantsCover($granted, collect($scopes)->pluck('id')->all());
    }

    /** Keeps an approval without consent within the access level the user last chose for this client. */
    #[Override]
    protected function approveRequest(AuthorizationRequestInterface $authRequest, ResponseInterface $psrResponse): Response
    {
        $client = $this->clients->find($authRequest->getClient()->getIdentifier());
        $resource = $this->resources->forRequest(request());
        $level = $client ? $resource?->highestIn($this->grantedScopes($authRequest->getUser()?->getIdentifier(), $client)) : null;

        if ($resource && $level !== null) {
            $authRequest->setScopes($resource->narrow($authRequest->getScopes(), $level));
        }

        return parent::approveRequest($authRequest, $psrResponse);
    }

    /**
     * The scopes on the user's active tokens for the client.
     *
     * @return list<string>
     */
    private function grantedScopes(mixed $userId, Client $client): array
    {
        return $client->tokens()
            ->where([
                ['user_id', '=', $userId],
                ['revoked', '=', false],
                ['expires_at', '>', Date::now()],
            ])
            ->pluck('scopes')
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }
}
