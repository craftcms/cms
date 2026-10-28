<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\Enums\McpScope;
use CraftCms\Cms\Mcp\Http\Responses\AuthorizationView;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Contracts\AuthorizationViewResponse;
use Laravel\Passport\Http\Controllers\AuthorizationController;
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

    /**
     * Skips the consent screen when the user already approved this client and the request
     * overlaps the MCP access level they chose then.
     */
    #[Override]
    protected function hasGrantedScopes(Authenticatable $user, Client $client, array $scopes): bool
    {
        $granted = $this->grantedScopes($user->getAuthIdentifier(), $client);
        $level = McpScope::highestIn($granted);

        if ($level === null) {
            return parent::hasGrantedScopes($user, $client, $scopes);
        }

        $requested = collect($scopes)->pluck('id');
        $requestedMcp = $requested->filter(fn (string $id): bool => McpScope::tryFrom($id) !== null);

        return $requested->diff($requestedMcp)->diff($granted)->isEmpty()
            && ($requestedMcp->isEmpty() || $requestedMcp->intersect($level->grantedValues())->isNotEmpty());
    }

    /** Keeps an approval without consent within the MCP access level the user last chose for this client. */
    #[Override]
    protected function approveRequest(AuthorizationRequestInterface $authRequest, ResponseInterface $psrResponse): Response
    {
        $client = $this->clients->find($authRequest->getClient()->getIdentifier());
        $level = $client ? McpScope::highestIn($this->grantedScopes($authRequest->getUser()?->getIdentifier(), $client)) : null;

        if ($level !== null) {
            $authRequest->setScopes(McpScope::narrow($authRequest->getScopes(), $level));
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
