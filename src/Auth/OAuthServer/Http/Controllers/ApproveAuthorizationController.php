<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer\Http\Controllers;

use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use Illuminate\Http\Request;
use Laravel\Passport\Http\Controllers\ApproveAuthorizationController as PassportApproveAuthorizationController;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use Override;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Approves an OAuth authorization request with the access level the user chose.
 *
 * @since 6.0.0
 */
class ApproveAuthorizationController extends PassportApproveAuthorizationController
{
    #[Override]
    public function approve(Request $request, ResponseInterface $psrResponse): Response
    {
        $authRequest = $this->getAuthRequestFromSession($request);
        $scopes = $authRequest->getScopes();

        $resource = app(OAuthResources::class)->forRequest($request);

        if ($resource) {
            $choices = $resource->choices(
                array_map(fn (ScopeEntityInterface $scope): string => $scope->getIdentifier(), $scopes),
                $request->craftUser(),
            );
            $chosen = $request->string('access')->value();

            $authRequest->setScopes($resource->narrow($scopes, in_array($chosen, $choices, true) ? $chosen : ($choices[0] ?? null)));
        }

        $authRequest->setAuthorizationApproved(true);

        return $this->withErrorHandling(fn () => $this->convertResponse(
            $this->server->completeAuthorizationRequest($authRequest, $psrResponse)
        ), $authRequest->getGrantTypeId() === 'implicit');
    }
}
