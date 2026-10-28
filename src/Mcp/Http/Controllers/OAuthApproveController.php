<?php

declare(strict_types=1);

namespace CraftCms\Cms\Mcp\Http\Controllers;

use CraftCms\Cms\Mcp\Enums\McpScope;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Approves an MCP authorization request with the access level the user chose.
 *
 * @since 6.0.0
 */
class OAuthApproveController extends ApproveAuthorizationController
{
    #[Override]
    public function approve(Request $request, ResponseInterface $psrResponse): Response
    {
        $authRequest = $this->getAuthRequestFromSession($request);
        $scopes = $authRequest->getScopes();

        $choices = McpScope::choices(
            array_map(fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes),
            $request->craftUser(),
        );
        $chosen = McpScope::tryFrom($request->string('access')->value());

        $authRequest->setScopes(McpScope::narrow($scopes, in_array($chosen, $choices, true) ? $chosen : ($choices[0] ?? null)));
        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $psrResponse)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }
}
