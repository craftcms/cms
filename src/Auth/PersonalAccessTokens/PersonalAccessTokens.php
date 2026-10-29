<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\PersonalAccessTokens;

use Closure;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\Models\User as UserModel;
use DateInterval;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Laravel\Passport\Scope;
use Laravel\Passport\Token;
use RuntimeException;

/**
 * Personal access tokens, which users create from **My Account → Tokens** for apps and scripts that can’t complete an
 * OAuth authorization flow.
 *
 * Tokens are Passport personal access tokens. APIs offer their Passport scopes for them by registering a
 * {@see ScopeGroup}, and the Tokens screen is only shown to users who may grant at least one registered scope:
 *
 * ```php
 * use CraftCms\Cms\Auth\PersonalAccessTokens\PersonalAccessTokens;
 * use CraftCms\Cms\Auth\PersonalAccessTokens\ScopeGroup;
 *
 * public function boot(PersonalAccessTokens $tokens): void
 * {
 *     $tokens->registerScopeGroup('my-plugin', fn () => new ScopeGroup(
 *         handle: 'my-plugin',
 *         label: 'My Plugin API',
 *         scopes: ['my-plugin:read' => 'Read my plugin’s data'],
 *     ));
 * }
 * ```
 *
 * Registered scopes are added to `Passport::tokensCan()`, alongside any scopes the application defines.
 *
 * @since 6.0.0
 */
#[Singleton]
class PersonalAccessTokens
{
    /**
     * @var array<string, Closure(): (ScopeGroup|null)>
     */
    private array $factories = [];

    public function __construct(
        private readonly Container $container,
        private readonly ClientRepository $clients,
    ) {}

    /** @param Closure(): (ScopeGroup|null) $factory */
    public function registerScopeGroup(string $handle, Closure $factory): void
    {
        if (isset($this->factories[$handle])) {
            throw new InvalidArgumentException("Personal access token scope group [$handle] is already registered.");
        }

        $this->factories[$handle] = $factory;
    }

    /**
     * The registered scope groups, keyed by handle.
     *
     * @return Collection<string, ScopeGroup>
     */
    public function scopeGroups(): Collection
    {
        $groups = new Collection;

        foreach ($this->factories as $handle => $factory) {
            $group = $this->container->call($factory);

            if ($group === null) {
                continue;
            }

            if (! $group instanceof ScopeGroup || $group->handle !== $handle) {
                throw new InvalidArgumentException("Personal access token scope group factory [$handle] must return a scope group with that handle.");
            }

            $groups->put($handle, $group);
        }

        return $groups;
    }

    /**
     * The scope groups the user may grant at least one scope from.
     *
     * @return Collection<string, ScopeGroup>
     */
    public function scopeGroupsFor(User $user): Collection
    {
        return $this->scopeGroups()->filter(fn (ScopeGroup $group) => $group->scopesFor($user) !== []);
    }

    /**
     * Adds the registered scopes to Passport’s, keeping the scopes already defined.
     *
     * @internal
     */
    public function registerPassportScopes(): void
    {
        Passport::tokensCan([
            ...$this->scopeGroups()->flatMap(fn (ScopeGroup $group) => $group->scopes)->all(),
            ...Passport::scopes()->mapWithKeys(fn (Scope $scope): array => [$scope->id => $scope->description])->all(),
        ]);
    }

    /**
     * Creates a personal access token, issued through Passport’s personal access client like `$user->createToken()`,
     * which this creates if it doesn’t exist yet.
     *
     * @param  string[]  $scopes
     * @return string The access token. It isn’t stored, so it can’t be retrieved again.
     *
     * @throws InvalidArgumentException if the user may not grant the scopes from the group
     */
    public function create(User $user, string $group, string $name, array $scopes, DateInterval $expiresIn): string
    {
        $scopeGroup = $this->scopeGroupsFor($user)->get($group);

        if (! $scopeGroup || $scopes === [] || array_diff($scopes, $scopeGroup->scopesFor($user)) !== []) {
            throw new InvalidArgumentException('Invalid scopes.');
        }

        $model = UserModel::findOrFail($user->id);
        $provider = $model->getProviderName();

        try {
            $this->clients->personalAccessClient($provider);
        } catch (RuntimeException) {
            $this->clients->createPersonalAccessGrantClient('Personal Access Client', $provider);
        }

        // Passport’s factory builds its authorization server, and reads the expiry, each time it’s resolved.
        $defaultExpiry = Passport::$personalAccessTokensExpireIn;
        Passport::personalAccessTokensExpireIn($expiresIn);

        try {
            return $model->createToken($name, array_values(array_unique($scopes)))->accessToken;
        } finally {
            Passport::$personalAccessTokensExpireIn = $defaultExpiry;
        }
    }

    /**
     * The user’s active personal access tokens, and the apps they’ve authorized, for the registered scope groups.
     *
     * Personal access tokens are listed individually, with their `tokenId`. Apps are listed once per client and scope
     * group, with a `tokenId` of `null`, and stay listed while they can still refresh their access.
     *
     * @return list<array{group: string, clientId: string, tokenId: string|null, name: string, scopes: string[], expiresAt: Carbon, personal: bool}>
     */
    public function grants(User $user): array
    {
        $groups = $this->scopeGroups();
        $grants = [];

        foreach ($this->activeTokens($user)->get() as $token) {
            /** @var string[] $scopes */
            $scopes = $token->getAttribute('scopes') ?? [];
            $group = $this->groupFor($groups, $scopes);

            if (! $group) {
                continue;
            }

            $client = $token->client;
            $clientId = (string) $token->getAttribute('client_id');
            $personal = $client->hasGrantType('personal_access');
            $key = $personal ? "token:{$token->getKey()}" : "client:$clientId:$group->handle";
            $grant = $grants[$key] ?? null;
            $expiresAt = $this->expiresAt($token);

            $grants[$key] = [
                'group' => $group->handle,
                'clientId' => $clientId,
                'tokenId' => $personal ? (string) $token->getKey() : null,
                'name' => (string) (($personal ? $token->getAttribute('name') : null) ?? $client->getAttribute('name')),
                'scopes' => array_values(array_unique([...$grant['scopes'] ?? [], ...$scopes])),
                'expiresAt' => $grant ? $grant['expiresAt']->max($expiresAt) : $expiresAt,
                'personal' => $personal,
            ];
        }

        return array_values($grants);
    }

    /**
     * Revokes the user’s access and refresh tokens for a client and scope group, or just one token if `$tokenId` is
     * given (as for personal access tokens).
     */
    public function revoke(User $user, string $group, string $clientId, ?string $tokenId = null): void
    {
        $scopeGroup = $this->scopeGroups()->get($group);

        if (! $scopeGroup) {
            return;
        }

        $tokens = Passport::token()->newQuery()
            ->where('user_id', $user->id)
            ->where('client_id', $clientId)
            ->when($tokenId !== null, fn (Builder $query) => $query->whereKey($tokenId))
            ->get()
            ->filter(fn (Token $token) => $scopeGroup->contains($token->getAttribute('scopes') ?? []));

        if ($tokens->isEmpty()) {
            return;
        }

        $ids = $tokens->modelKeys();
        Passport::token()->newQuery()->whereKey($ids)->update(['revoked' => true]);
        Passport::refreshToken()->newQuery()->whereIn('access_token_id', $ids)->update(['revoked' => true]);
    }

    /**
     * @return Builder<Token>
     */
    private function activeTokens(User $user): Builder
    {
        $now = Query::prepareDateForDb(now());

        return Passport::token()->newQuery()
            ->with(['client', 'refreshToken'])
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->whereHas('client', fn (Builder $query) => $query->where('revoked', false))
            ->where(fn (Builder $query) => $query
                ->where('expires_at', '>', $now)
                ->orWhereHas('refreshToken', fn (Builder $query) => $query
                    ->where('revoked', false)
                    ->where('expires_at', '>', $now)))
            ->latest();
    }

    /**
     * When the token stops working: when it expires, or for an app that can refresh it, when its refresh token does.
     */
    private function expiresAt(Token $token): Carbon
    {
        /** @var Carbon $expiresAt */
        $expiresAt = $token->getAttribute('expires_at');
        $refreshToken = $token->refreshToken;

        if ($refreshToken && ! $refreshToken->getAttribute('revoked')) {
            return $expiresAt->max($refreshToken->getAttribute('expires_at'));
        }

        return $expiresAt;
    }

    /**
     * The group a token belongs to: the one that defines all of its scopes.
     *
     * @param  Collection<string, ScopeGroup>  $groups
     * @param  string[]  $scopes
     */
    private function groupFor(Collection $groups, array $scopes): ?ScopeGroup
    {
        return $groups->first(fn (ScopeGroup $group) => $group->contains($scopes));
    }
}
