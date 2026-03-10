<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use Closure;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use CraftCms\Cms\User\Elements\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Uri;
use Laravel\Socialite\Contracts\Provider as ProviderContract;
use Laravel\Socialite\Facades\Socialite;
use SensitiveParameter;

use function CraftCms\Cms\t;

class Provider extends Component
{
    public protected(set) string $driver;

    public protected(set) string $name;

    /** @var string[] */
    public protected(set) array $scopes = [];

    /** @var array<string, mixed> */
    public protected(set) array $with = [];

    public protected(set) ?string $clientId = null;

    public protected(set) ?string $clientSecret = null;

    public protected(set) ?string $redirectUrl = null;

    public protected(set) bool $stateless = false;

    public protected(set) bool $activatesUsers = false;

    /** @var (Closure(ProviderProfile): (string|int))|null */
    public protected(set) ?Closure $uniqueIdCallback = null;

    /** @var (Closure(ProviderProfile): (User|null))|null */
    public protected(set) ?Closure $findUserCallback = null;

    /** @var (Closure(User, ProviderProfile): (User|null))|null */
    public protected(set) ?Closure $populateUserCallback = null;

    /** @var (Closure(array<int, int>, ProviderProfile): (int|string|array<int|string, int|string>|null))|int|string|array<int|string, int|string>|null */
    public protected(set) Closure|int|string|array|null $assignUserGroups = null;

    public function __construct(
        public string $handle,
        array $config = [],
    ) {
        $config['driver'] ??= $handle;
        $config['name'] ??= Str::headline($handle);

        parent::__construct($config);
    }

    public function handle(string $value): static
    {
        $this->handle = $value;

        return $this;
    }

    public function driver(string $value): static
    {
        $this->driver = $value;

        return $this;
    }

    public function name(string $value): static
    {
        $this->name = $value;

        return $this;
    }

    /**
     * @param  string[]  $value
     */
    public function scopes(array $value): static
    {
        $this->scopes = array_values(array_filter($value, is_string(...)));

        return $this;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    public function with(array $value): static
    {
        $this->with = $value;

        return $this;
    }

    public function clientId(#[SensitiveParameter] ?string $value): static
    {
        $this->clientId = $value;

        return $this;
    }

    public function clientSecret(#[SensitiveParameter] ?string $value): static
    {
        $this->clientSecret = $value;

        return $this;
    }

    public function redirectUrl(?string $value): static
    {
        $this->redirectUrl = $value;

        return $this;
    }

    public function stateless(bool $value = true): static
    {
        $this->stateless = $value;

        return $this;
    }

    public function activatesUsers(bool $value = true): static
    {
        $this->activatesUsers = $value;

        return $this;
    }

    /** @param (Closure(ProviderProfile): (string|int|null)) $value */
    public function determineUniqueIdUsing(Closure $value): static
    {
        $this->uniqueIdCallback = $value;

        return $this;
    }

    /** @param Closure(ProviderProfile): (User|null) $value */
    public function findUserUsing(Closure $value): static
    {
        $this->findUserCallback = $value;

        return $this;
    }

    /** @param Closure(User, ProviderProfile): (User|null) $value */
    public function populateUserUsing(Closure $value): static
    {
        $this->populateUserCallback = $value;

        return $this;
    }

    /** @param (Closure(array<int, int>, ProviderProfile): (int|string|array<int|string, int|string>))|int|string|array<int|string, int|string> $value */
    public function assignUserGroups(Closure|int|string|array $value): static
    {
        $this->assignUserGroups = $value;

        return $this;
    }

    #[AllowedInSandbox]
    public function renderButton(): HtmlString
    {
        return new HtmlString(Html::a(
            t('Sign in with {name}', ['name' => $this->name]),
            $this->getLoginUrl(),
            ['class' => 'btn'],
        ));
    }

    public function getProfile(): ProviderProfile
    {
        return ProviderProfile::fromSocialite(
            $this->handle,
            $this->getDriver()->user(),
        );
    }

    public function getDriver(): ProviderContract
    {
        Config::set("services.{$this->driver}", $this->getCredentials());

        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver($this->driver);

        if ($this->stateless) {
            $driver = $driver->stateless();
        }

        if (! empty($this->scopes)) {
            $driver = $driver->scopes($this->scopes);
        }

        if ($this->with !== []) {
            $driver = $driver->with($this->with);
        }

        return $driver;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCredentials(): array
    {
        $credentials = array_merge(
            (array) Config::get("services.{$this->driver}", []),
            (array) Config::get("services.{$this->handle}", []),
            Arr::whereNotNull([
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect' => $this->getCallbackUrl(),
            ]),
        );

        $credentials['redirect'] ??= $this->getCallbackUrl();

        return $credentials;
    }

    private function getCallbackUrl(): string
    {
        $isCpRequest = request()->boolean('cp');

        $url = $this->redirectUrl ?? route('craft.auth.socialite.callback', [
            'provider' => $this->handle,
            ...($isCpRequest ? ['cp' => 1] : []),
        ]);

        if (! $isCpRequest) {
            return $url;
        }

        return Uri::of($url)
            ->withQueryIfMissing(['cp' => 1])
            ->value();
    }

    protected function getLoginUrl(): string
    {
        $parameters = ['provider' => $this->handle];

        if (request()->isCpRequest()) {
            $parameters['cp'] = 1;
        }

        if ($returnUrl = request()->query('returnUrl')) {
            $parameters['returnUrl'] = $returnUrl;
        }

        return route('craft.auth.socialite.redirect', $parameters, false);
    }

    /**
     * Restores the state of an object from an array. This
     * is used when the config is cached by Laravel.
     */
    public static function __set_state(array $stateData): static
    {
        /** @phpstan-ignore new.static */
        return new static($stateData['handle'], $stateData);
    }
}
