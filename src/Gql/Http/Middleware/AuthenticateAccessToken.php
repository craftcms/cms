<?php

declare(strict_types=1);

namespace CraftCms\Cms\Gql\Http\Middleware;

use Closure;
use CraftCms\Cms\Auth\OAuthServer\OAuthResources;
use CraftCms\Cms\Gql\Gql;
use CraftCms\Cms\Http\Middleware\PreventRequestsDuringMaintenance;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Models\User as OAuthUser;
use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;

use function CraftCms\Cms\craftAuth;

/**
 * Authenticates the GraphQL API’s access tokens before its control panel and maintenance mode checks, so the token’s
 * user goes through the same checks as any other signed-in request.
 *
 * The route skips the usual maintenance check, which runs before this and only sees the session user.
 *
 * @since 6.0.0
 */
readonly class AuthenticateAccessToken
{
    /** The request attribute that holds the access token’s user. */
    public const string USER_ATTRIBUTE = 'craft.gqlAccessTokenUser';

    public function __construct(
        private OAuthResources $oauthResources,
        private MaintenanceMode $maintenanceMode,
        private PreventRequestsDuringMaintenance $maintenanceMiddleware,
    ) {}

    /** @param Closure(Request): mixed $next */
    public function handle(Request $request, Closure $next): mixed
    {
        // Preflight requests stay anonymous, and admins’ X-Craft-Gql-Schema requests use their session.
        // Access tokens are JWTs, whereas GraphQL tokens never contain dots.
        $bearerToken = $request->bearerToken();

        if (
            ! $request->isMethod('OPTIONS') &&
            ! $request->headers->has('X-Craft-Gql-Schema') &&
            $bearerToken &&
            substr_count($bearerToken, '.') === 2
        ) {
            $request->attributes->set(self::USER_ATTRIBUTE, $this->authenticate($request));

            abort_if($request->isCpRequest() && ! Gate::check('accessCp'), 403);
        }

        abort_if($this->maintenanceMode->active() && ! $this->maintenanceMiddleware->allowsAccess($request), 503);

        return $next($request);
    }

    private function authenticate(Request $request): OAuthUser
    {
        $user = $request->user(Gql::AUTH_GUARD);
        $resource = $this->oauthResources->get('graphql');

        if (
            ! $user instanceof OAuthUser ||
            $user->asElement()->getStatus() !== UserElement::STATUS_ACTIVE ||
            ! $resource ||
            ! array_any($resource->scopes(), $user->tokenCan(...))
        ) {
            throw new HttpException(401, 'Invalid access token.', headers: [
                'WWW-Authenticate' => $resource?->challenge('invalid_token') ?? 'Bearer error="invalid_token"',
            ]);
        }

        // Act as the user for the rest of the request, so permission checks and element queries apply to them
        craftAuth()->setUser($user);

        return $user;
    }
}
