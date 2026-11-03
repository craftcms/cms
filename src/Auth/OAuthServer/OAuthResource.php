<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuthServer;

use Closure;
use CraftCms\Cms\User\Contracts\CraftUser;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/**
 * An API that OAuth clients can register for and request access to, such as the authenticated MCP server or the
 * GraphQL API.
 *
 * Its scopes are access levels, ordered from narrowest to broadest. Each level includes the ones before it, and the
 * user chooses one on the consent screen. Scopes only narrow what the user’s permissions already allow.
 *
 * @since 6.0.0
 */
readonly class OAuthResource
{
    /**
     * @param  string  $handle  The resource’s handle, which its OAuth route names include.
     * @param  array<string, array{label: string, description: string}>  $levels  The scopes, narrowest first.
     * @param  string  $resourceUrl  The URL of the protected API.
     * @param  string  $authorizationRoute  The path that the registration and authorization endpoints are under.
     * @param  string  $baseUrl  The URL that `$authorizationRoute` and the endpoints are relative to.
     * @param  (Closure(CraftUser): list<string>)|null  $userLevels  Returns the scopes a user may grant. All of them by default.
     * @param  string  $routeName  The prefix for the OAuth route names.
     */
    public function __construct(
        public string $handle,
        public array $levels,
        public string $resourceUrl,
        public string $authorizationRoute,
        public string $baseUrl,
        public ?Closure $userLevels = null,
        public string $routeName = '',
    ) {}

    /** @return list<string> */
    public function scopes(): array
    {
        return array_keys($this->levels);
    }

    public function owns(string $scope): bool
    {
        return isset($this->levels[$scope]);
    }

    /**
     * The levels a user may choose between when approving a client that requested `$requested`.
     *
     * @param  list<string>  $requested
     * @return list<string>
     */
    public function choices(array $requested, CraftUser $user): array
    {
        $allowed = $this->userLevels ? ($this->userLevels)($user) : $this->scopes();

        return array_values(array_filter(
            $this->scopes(),
            fn (string $scope): bool => in_array($scope, $requested, true) && in_array($scope, $allowed, true),
        ));
    }

    /**
     * The broadest level in `$scopes`.
     *
     * @param  list<string>  $scopes
     */
    public function highestIn(array $scopes): ?string
    {
        return collect($this->scopes())->last(fn (string $scope): bool => in_array($scope, $scopes, true));
    }

    /**
     * The scopes granted by choosing `$level`: it and the narrower ones.
     *
     * @return list<string>
     */
    public function grantedBy(string $level): array
    {
        $index = array_search($level, $this->scopes(), true);

        return $index === false ? [] : array_slice($this->scopes(), 0, $index + 1);
    }

    /**
     * The scopes that satisfy `$level`: it and the broader ones.
     *
     * @return list<string>
     */
    public function satisfying(string $level): array
    {
        $index = array_search($level, $this->scopes(), true);

        return $index === false ? [] : array_slice($this->scopes(), $index);
    }

    /**
     * Removes the requested levels that `$level` doesn’t grant, keeping scopes that belong to other APIs.
     *
     * @param  list<ScopeEntityInterface>  $scopes
     * @return list<ScopeEntityInterface>
     */
    public function narrow(array $scopes, ?string $level): array
    {
        $granted = $level !== null ? $this->grantedBy($level) : [];

        return array_values(array_filter(
            $scopes,
            fn (ScopeEntityInterface $scope): bool => ! $this->owns($scope->getIdentifier())
                || in_array($scope->getIdentifier(), $granted, true),
        ));
    }

    /**
     * Whether earlier grants cover the requested scopes: scopes from other APIs must all have been granted, and the
     * requested levels must overlap the ones granted by the broadest level chosen before.
     *
     * @param  list<string>  $granted
     * @param  list<string>  $requested
     */
    public function grantsCover(array $granted, array $requested): bool
    {
        $level = $this->highestIn($granted);
        $requestedLevels = array_values(array_filter($requested, $this->owns(...)));

        if (array_diff($requested, $requestedLevels, $granted) !== []) {
            return false;
        }

        return $requestedLevels === [] || ($level !== null && array_intersect($requestedLevels, $this->grantedBy($level)) !== []);
    }

    public function issuer(): string
    {
        return $this->url($this->authorizationRoute);
    }

    /** The URL for a path, relative to the resource’s base URL. */
    public function url(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
    }

    public function resourceMetadataUrl(): string
    {
        return $this->wellKnownUrl('oauth-protected-resource', $this->resourceUrl);
    }

    public function authorizationServerMetadataUrl(): string
    {
        return $this->wellKnownUrl('oauth-authorization-server', $this->issuer());
    }

    /** The `WWW-Authenticate` header for requests without a valid access token. */
    public function challenge(?string $error = null): string
    {
        return sprintf(
            'Bearer %sresource_metadata="%s", scope="%s"',
            $error !== null ? "error=\"$error\", " : '',
            $this->resourceMetadataUrl(),
            implode(' ', $this->scopes()),
        );
    }

    /** @return array<string, mixed> */
    public function protectedResourceMetadata(): array
    {
        return [
            'resource' => $this->resourceUrl,
            'authorization_servers' => [$this->issuer()],
            'scopes_supported' => $this->scopes(),
            'bearer_methods_supported' => ['header'],
        ];
    }

    /** @return array<string, mixed> */
    public function authorizationServerMetadata(): array
    {
        return [
            'issuer' => $this->issuer(),
            'authorization_endpoint' => $this->routeUrl('authorize'),
            'token_endpoint' => $this->url(route('passport.token', absolute: false)),
            'registration_endpoint' => $this->routeUrl('register'),
            'token_endpoint_auth_methods_supported' => ['none', 'client_secret_basic', 'client_secret_post'],
            'response_types_supported' => ['code'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'scopes_supported' => $this->scopes(),
            'code_challenge_methods_supported' => ['S256'],
        ];
    }

    /** The URL of one of the resource’s OAuth routes, such as `authorize` or `register`. */
    public function routeUrl(string $name): string
    {
        return $this->url(route("$this->routeName.$name", absolute: false));
    }

    private function wellKnownUrl(string $suffix, string $url): string
    {
        $parts = parse_url($url);
        $origin = ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ":{$parts['port']}" : '');

        return "$origin/.well-known/$suffix".rtrim($parts['path'] ?? '', '/');
    }
}
