<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use Composer\InstalledVersions;
use CraftCms\Cms\Auth\Models\SsoIdentity;
use CraftCms\Cms\Auth\OAuth\Actions\ButtonRenderer;
use CraftCms\Cms\Auth\OAuth\Actions\IdentityResolver;
use CraftCms\Cms\Auth\OAuth\Actions\UserGroupResolver;
use CraftCms\Cms\Auth\OAuth\Actions\UserPopulator;
use CraftCms\Cms\Auth\OAuth\Actions\UserResolver;
use CraftCms\Cms\Auth\OAuth\Contracts\PopulatesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Contracts\RendersOAuthButton;
use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthIdentity;
use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUser;
use CraftCms\Cms\Auth\OAuth\Contracts\ResolvesOAuthUserGroups;
use CraftCms\Cms\Auth\OAuth\Data\ButtonData;
use CraftCms\Cms\Auth\OAuth\Data\ProviderDefinition;
use CraftCms\Cms\Auth\OAuth\Exceptions\ProviderConfigurationException;
use CraftCms\Cms\Config\GeneralConfig;
use CraftCms\Cms\Database\Table;
use CraftCms\Cms\Edition;
use CraftCms\Cms\ProjectConfig\ProjectConfig;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Query;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use CraftCms\Cms\User\Elements\User;
use CraftCms\Cms\User\UserGroups;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\SocialiteManager;
use Laravel\Socialite\Two\AbstractProvider;
use RuntimeException;
use Throwable;

use function CraftCms\Cms\t;

#[Scoped]
class OAuth
{
    public const string CONNECT_SESSION_KEY = 'craft.oauth.connect';

    private const string CP_CONTEXT_VALUE = 'cp';

    public const array DRIVER_ICONS = [
        'apple' => 'apple',
        'azure' => 'microsoft',
        'azure-ad' => 'microsoft',
        'bitbucket' => 'bitbucket',
        'discord' => 'discord',
        'facebook' => 'facebook',
        'github' => 'github',
        'gitlab' => 'gitlab',
        'google' => 'google',
        'linkedin' => 'linkedin',
        'linkedin-openid' => 'linkedin',
        'microsoft' => 'microsoft',
        'slack' => 'slack',
        'slack-openid' => 'slack',
        'twitter' => 'twitter',
        'twitter-oauth-2' => 'x-twitter',
        'x' => 'x-twitter',
        'x-twitter' => 'x-twitter',
    ];

    /** @var Collection<string, ProviderDefinition>|null */
    private ?Collection $providerDefinitions = null;

    private readonly ?SocialiteFactory $socialite;

    public function __construct(
        private readonly GeneralConfig $generalConfig,
        private readonly ProjectConfig $projectConfig,
        private readonly UserGroups $userGroups,
    ) {
        $this->socialite = self::isAvailable()
            ? app(SocialiteFactory::class)
            : null;
    }

    public static function isAvailable(): bool
    {
        return Edition::get()->supportsOAuth() && InstalledVersions::isInstalled('laravel/socialite');
    }

    /**
     * @return Collection<string, ProviderDefinition>
     */
    public function getProviderDefinitions(): Collection
    {
        if ($this->providerDefinitions !== null) {
            return $this->providerDefinitions;
        }

        if (! self::isAvailable()) {
            return $this->providerDefinitions = collect();
        }

        return $this->providerDefinitions = collect($this->generalConfig->oauthProviders)
            ->mapWithKeys(function (mixed $config, int|string $handle): array {
                try {
                    [$handle, $config] = $this->normalizeProviderConfigEntry($handle, $config);
                    $definition = $this->normalizeProvider($handle, $config);
                } catch (ProviderConfigurationException $e) {
                    if (app()->hasDebugModeEnabled()) {
                        throw $e;
                    }

                    report($e);

                    return [];
                }

                return $definition !== null ? [$handle => $definition] : [];
            });
    }

    public function getProviderDefinition(string $handle): ?ProviderDefinition
    {
        return $this->getProviderDefinitions()->get($handle);
    }

    /**
     * @return HtmlString[]
     */
    public function getLoginButtons(?bool $isCpRequest = null): array
    {
        $isCpRequest ??= $this->isCpRequest();

        return $this->getProviderDefinitions()
            ->map(function (ProviderDefinition $provider) use ($isCpRequest): ?HtmlString {
                try {
                    return $this->renderButton($provider, $isCpRequest);
                } catch (RuntimeException $e) {
                    report($e);
                    Log::error($e->getMessage(), [__METHOD__]);

                    return null;
                }
            })
            ->filter()
            ->values()
            ->all();
    }

    public function buildProvider(ProviderDefinition $provider, ?bool $isCpRequest = null): SocialiteProvider
    {
        $isCpRequest ??= $this->isCpRequest();

        $driver = $provider->providerClass !== null
            ? $this->buildProviderInstance($provider->providerClass, $provider, $isCpRequest)
            : $this->resolveNamedDriver($provider, $isCpRequest);

        if ($provider->scopes !== [] && method_exists($driver, 'scopes')) {
            $driver->scopes($provider->scopes);
        }

        if ($provider->with !== [] && method_exists($driver, 'with')) {
            $driver->with($provider->with);
        }

        if ($provider->stateless && method_exists($driver, 'stateless')) {
            $driver->stateless();
        }

        return $driver;
    }

    public function redirectPath(ProviderDefinition $provider, bool $isCpRequest = false): string
    {
        $path = sprintf('oauth/%s/redirect', $provider->handle);

        return $isCpRequest ? Url::cpUrl($path) : Url::siteUrl($path);
    }

    public function callbackPath(ProviderDefinition $provider, bool $isCpRequest = false): string
    {
        return Url::siteUrl(
            sprintf('oauth/%s/callback', $provider->handle),
            $isCpRequest ? ['context' => self::CP_CONTEXT_VALUE] : [],
        );
    }

    public function resolveIdentity(ProviderDefinition $provider, SocialiteUser $socialiteUser): string
    {
        $resolver = $this->resolveStrategy($provider->identityResolver, ResolvesOAuthIdentity::class);

        return $resolver->handle($provider, $socialiteUser);
    }

    public function resolveUser(ProviderDefinition $provider, SocialiteUser $socialiteUser, string $identity): ?User
    {
        $resolver = $this->resolveStrategy($provider->userResolver, ResolvesOAuthUser::class);

        return $resolver->handle($provider, $socialiteUser, $identity);
    }

    public function populateUser(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        User $user,
        string $identity,
        bool $isNew,
    ): User {
        $populator = $this->resolveStrategy($provider->userPopulator, PopulatesOAuthUser::class);

        return $populator->handle($provider, $socialiteUser, $user, $identity, $isNew);
    }

    /**
     * @return int[]
     */
    public function resolveGroupIds(
        ProviderDefinition $provider,
        SocialiteUser $socialiteUser,
        User $user,
        string $identity,
    ): array {
        $resolver = $this->resolveStrategy($provider->groupResolver, ResolvesOAuthUserGroups::class);

        return collect($resolver->handle($provider, $socialiteUser, $user, $identity))
            ->map(fn (mixed $group) => $this->resolveGroupReference($group, fn () => new RuntimeException(
                sprintf('OAuth provider [%s] returned an unknown user group.', $provider->handle),
            )))
            ->unique()
            ->values()
            ->all();
    }

    public function renderButton(ProviderDefinition $provider, bool $isCpRequest): HtmlString
    {
        $renderer = $this->resolveStrategy($provider->buttonRenderer, RendersOAuthButton::class);

        return $renderer->handle(new ButtonData(
            provider: $provider,
            isCpRequest: $isCpRequest,
            url: $this->redirectPath($provider, $isCpRequest),
            label: $provider->label,
        ));
    }

    public function findLinkedUser(ProviderDefinition $provider, string $identity): ?User
    {
        $userId = SsoIdentity::query()
            ->where('provider', $provider->handle)
            ->where('identityId', $identity)
            ->orderByDesc('dateUpdated')
            ->value('userId');

        return $userId ? User::findOne($userId) : null;
    }

    public function linkedUserId(ProviderDefinition $provider, string $identity): ?int
    {
        $userId = SsoIdentity::query()
            ->where('provider', $provider->handle)
            ->where('identityId', $identity)
            ->orderByDesc('dateUpdated')
            ->value('userId');

        return is_numeric($userId) ? (int) $userId : null;
    }

    public function identityFor(User $user, ProviderDefinition $provider): ?string
    {
        $identity = SsoIdentity::query()
            ->where('userId', $user->id)
            ->where('provider', $provider->handle)
            ->orderByDesc('dateUpdated')
            ->value('identityId');

        return is_string($identity) ? $identity : null;
    }

    public function findUserByEmail(string $email): ?User
    {
        $email = trim($email);

        if ($email === '') {
            return null;
        }

        return User::find()
            ->addSelect([
                'users.password as password',
                'users.passwordResetRequired as passwordResetRequired',
            ])
            ->email(Query::escapeParam($email))
            ->status(null)
            ->first();
    }

    public function hasIdentity(int $userId): bool
    {
        return SsoIdentity::query()->where('userId', $userId)->exists();
    }

    public function unlinkIdentity(User $user, ProviderDefinition $provider): void
    {
        SsoIdentity::query()
            ->where('userId', $user->id)
            ->where('provider', $provider->handle)
            ->delete();
    }

    /**
     * @return array{0: string, 1: mixed}
     */
    private function normalizeProviderConfigEntry(int|string $handle, mixed $config): array
    {
        if (is_int($handle)) {
            if (! is_string($config) || $config === '') {
                throw new ProviderConfigurationException('OAuth providers configured without an explicit handle must be non-empty driver strings.');
            }

            return [$config, $config];
        }

        return [$handle, $config];
    }

    public function canCreateUsers(ProviderDefinition $provider): bool
    {
        if ($provider->createsUsers === false) {
            return false;
        }

        if ($provider->createsUsers === true) {
            return true;
        }

        return $this->publicRegistrationIsAllowed();
    }

    public function publicRegistrationIsAllowed(): bool
    {
        return (bool) $this->projectConfig->get(sprintf('%s.allowPublicRegistration', ProjectConfig::PATH_USERS));
    }

    public function linkIdentity(User $user, ProviderDefinition $provider, string $identity): void
    {
        DB::transaction(function () use ($user, $provider, $identity) {
            DB::table(Table::SSO_IDENTITIES)
                ->where('provider', $provider->handle)
                ->where(function (Builder $query) use ($user, $identity) {
                    $query
                        ->where('userId', $user->id)
                        ->orWhere('identityId', $identity);
                })
                ->delete();

            DB::table(Table::SSO_IDENTITIES)->insert([
                'provider' => $provider->handle,
                'identityId' => $identity,
                'userId' => $user->id,
                'dateCreated' => $now = now('UTC'),
                'dateUpdated' => $now,
            ]);
        });
    }

    private function normalizeProvider(string $handle, mixed $config): ?ProviderDefinition
    {
        if (is_string($config)) {
            $config = ['driver' => $config];
        }

        if (! is_array($config)) {
            throw new ProviderConfigurationException(sprintf(
                'OAuth provider [%s] must be configured as a driver string or an array.',
                $handle,
            ));
        }

        $config = $this->validateProviderConfig($handle, $config);
        $enabled = (bool) ($config['enabled'] ?? true);

        if (! $enabled) {
            return null;
        }

        $driver = $config['driver'];
        $clientId = $config['clientId'] ?? null;
        $clientSecret = $config['clientSecret'] ?? null;
        $providerClass = $this->resolveProviderClass($handle, $driver);
        $name = ($config['name'] ?? null) ?: Str::headline($handle);
        $label = ($config['label'] ?? null) ?: t('Sign in with {name}', ['name' => $name]);
        $icon = $config['icon'] ?? self::DRIVER_ICONS[$driver] ?? null;

        $definition = new ProviderDefinition(
            handle: $handle,
            driver: $driver,
            providerClass: $providerClass,
            name: $name,
            label: $label,
            clientId: $clientId !== null ? (string) $clientId : null,
            clientSecret: $clientSecret !== null ? (string) $clientSecret : null,
            icon: $icon,
            stateless: (bool) ($config['stateless'] ?? false),
            createsUsers: array_key_exists('createsUsers', $config)
                ? ($config['createsUsers'] === null ? null : (bool) $config['createsUsers'])
                : null,
            activatesUsers: (bool) ($config['activatesUsers'] ?? false),
            trustsEmail: array_key_exists('trustsEmail', $config) && (bool) $config['trustsEmail'],
            scopes: array_values($config['scopes'] ?? []),
            with: $config['with'] ?? [],
            groupIds: $this->resolveConfiguredGroups($handle, $config['groups'] ?? []),
            identityResolver: $this->resolveStrategyClass($handle, $config['identityResolver'] ?? IdentityResolver::class, ResolvesOAuthIdentity::class),
            userResolver: $this->resolveStrategyClass($handle, $config['userResolver'] ?? UserResolver::class, ResolvesOAuthUser::class),
            userPopulator: $this->resolveStrategyClass($handle, $config['userPopulator'] ?? UserPopulator::class, PopulatesOAuthUser::class),
            groupResolver: $this->resolveStrategyClass($handle, $config['groupResolver'] ?? UserGroupResolver::class, ResolvesOAuthUserGroups::class),
            buttonRenderer: $this->resolveStrategyClass($handle, $config['buttonRenderer'] ?? ButtonRenderer::class, RendersOAuthButton::class),
        );

        if ($definition->providerClass === null) {
            $this->assertNamedDriverSupported($definition);
        } else {
            $this->assertDirectProviderCredentials($definition);
        }

        return $definition;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function validateProviderConfig(string $handle, array $config): array
    {
        try {
            return Validator::make($config, [
                'enabled' => ['sometimes', 'boolean'],
                'driver' => ['required', 'string', 'filled'],
                'clientId' => ['sometimes', 'nullable', 'string', 'filled'],
                'clientSecret' => ['sometimes', 'nullable', 'string', 'filled'],
                'name' => ['sometimes', 'nullable', 'string', 'filled'],
                'label' => ['sometimes', 'nullable', 'string', 'filled'],
                'icon' => ['sometimes', 'nullable', 'string', 'filled'],
                'scopes' => ['sometimes', 'array'],
                'scopes.*' => ['string', 'filled'],
                'with' => ['sometimes', 'array'],
                'stateless' => ['sometimes', 'boolean'],
                'groups' => ['sometimes'],
                'createsUsers' => ['sometimes', 'nullable', 'boolean'],
                'activatesUsers' => ['sometimes', 'boolean'],
                'trustsEmail' => ['sometimes', 'boolean'],
                'identityResolver' => ['sometimes', 'string'],
                'userResolver' => ['sometimes', 'string'],
                'userPopulator' => ['sometimes', 'string'],
                'groupResolver' => ['sometimes', 'string'],
                'buttonRenderer' => ['sometimes', 'string'],
            ])->validate();
        } catch (ValidationException $e) {
            throw new ProviderConfigurationException(sprintf(
                'OAuth provider [%s] has invalid configuration: %s',
                $handle,
                $e->validator->errors()->first(),
            ), previous: $e);
        }
    }

    /**
     * @return int[]
     */
    private function resolveConfiguredGroups(string $handle, mixed $value): array
    {
        return collect(Arr::wrap($value))
            ->map(fn (mixed $group) => $this->resolveGroupReference($group, fn () => new ProviderConfigurationException(
                sprintf('OAuth provider [%s] references an unknown user group.', $handle),
            )))
            ->unique()
            ->values()
            ->all();
    }

    private function resolveGroupReference(mixed $group, callable $onFailure): int
    {
        $resolved = $this->userGroups->resolveGroup($group);

        if (! $resolved?->id) {
            throw $onFailure();
        }

        return $resolved->id;
    }

    private function resolveProviderClass(string $handle, string $driver): ?string
    {
        if (! class_exists($driver)) {
            return null;
        }

        if (! is_subclass_of($driver, AbstractProvider::class)) {
            throw new ProviderConfigurationException(sprintf('OAuth provider [%s] has an unsupported driver [%s].', $handle, $driver));
        }

        return $driver;
    }

    private function buildProviderInstance(string $providerClass, ProviderDefinition $provider, bool $isCpRequest): AbstractProvider
    {
        $this->assertDirectProviderCredentials($provider);

        return new $providerClass(
            request(),
            $provider->clientId,
            $provider->clientSecret,
            $this->callbackPath($provider, $isCpRequest),
            [],
        );
    }

    private function resolveNamedDriver(ProviderDefinition $provider, bool $isCpRequest): SocialiteProvider
    {
        Config::set("services.{$provider->driver}", $this->resolveNamedDriverConfig($provider, $isCpRequest));

        if ($this->socialite instanceof SocialiteManager) {
            $this->socialite->forgetDrivers();
        }

        return $this->socialite->driver($provider->driver);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveNamedDriverConfig(ProviderDefinition $provider, bool $isCpRequest): array
    {
        $config = Config::get("services.{$provider->driver}", []);

        if (! is_array($config)) {
            $config = [];
        }

        if ($provider->clientId !== null) {
            $config['client_id'] = $provider->clientId;
        }

        if ($provider->clientSecret !== null) {
            $config['client_secret'] = $provider->clientSecret;
        }

        $config['redirect'] = $this->callbackPath($provider, $isCpRequest);

        if ($provider->scopes !== []) {
            $config['scopes'] = $provider->scopes;
        }

        return $config;
    }

    private function assertNamedDriverSupported(ProviderDefinition $provider): void
    {
        try {
            $this->resolveNamedDriver($provider, false);
        } catch (Throwable $e) {
            throw new ProviderConfigurationException(sprintf(
                'OAuth provider [%s] has an unsupported driver [%s].',
                $provider->handle,
                $provider->driver,
            ), previous: $e);
        }
    }

    private function assertDirectProviderCredentials(ProviderDefinition $provider): void
    {
        if ($provider->clientId !== null && $provider->clientSecret !== null) {
            return;
        }

        throw new ProviderConfigurationException(sprintf(
            'OAuth provider [%s] must define [clientId] and [clientSecret] when using a provider class.',
            $provider->handle,
        ));
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $interface
     * @return class-string<T>
     */
    private function resolveStrategyClass(string $handle, mixed $class, string $interface): string
    {
        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, $interface)) {
            throw new ProviderConfigurationException(sprintf(
                'OAuth provider [%s] has an invalid strategy class [%s]; expected implementation of [%s].',
                $handle,
                (string) $class,
                $interface,
            ));
        }

        return $class;
    }

    /**
     * @template T of object
     *
     * @param  class-string<T>  $interface
     * @return T
     */
    private function resolveStrategy(string $class, string $interface): object
    {
        $strategy = app($class);

        if (! $strategy instanceof $interface) {
            throw new RuntimeException(sprintf('OAuth strategy [%s] must implement [%s].', $class, $interface));
        }

        return $strategy;
    }

    private function isCpRequest(): bool
    {
        $request = request();
        if ($request->isCpRequest()) {
            return true;
        }

        return $request->query('context') === self::CP_CONTEXT_VALUE;
    }
}
