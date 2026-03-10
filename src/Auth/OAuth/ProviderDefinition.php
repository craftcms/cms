<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use SensitiveParameter;

use function CraftCms\Cms\t;

class ProviderDefinition extends Component
{
    public string $driver;

    public string $name;

    /** @var string[] */
    public array $scopes = [];

    /** @var array<string, mixed> $with */
    public array $with = [];

    public ?string $clientId = null;

    public ?string $clientSecret = null;

    public ?string $redirectUrl = null;

    public bool $stateless = false;

    public mixed $idpUniqueIdentifier = null;

    public mixed $findUser = null;

    public mixed $populateUser = null;

    public mixed $assignUserGroups = null;

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

    public function stateless(bool $value): static
    {
        $this->stateless = $value;

        return $this;
    }

    public function idpUniqueIdentifier(mixed $value): static
    {
        $this->idpUniqueIdentifier = $value;

        return $this;
    }

    public function findUser(mixed $value): static
    {
        $this->findUser = $value;

        return $this;
    }

    public function populateUser(mixed $value): static
    {
        $this->populateUser = $value;

        return $this;
    }

    public function assignUserGroups(mixed $value): static
    {
        $this->assignUserGroups = $value;

        return $this;
    }

    #[AllowedInSandbox]
    public function renderCpButton(?string $label = null): string
    {
        return Html::a(
            $label ?? t('Sign in with {name}', ['name' => $this->name]),
            $this->loginUrl(true),
            ['class' => 'btn'],
        );
    }

    #[AllowedInSandbox]
    public function renderSiteLink(?string $label = null): string
    {
        return Html::a(
            $label ?? t('Sign in with {name}', ['name' => $this->name]),
            $this->loginUrl(),
        );
    }

    /**
     * Restores the state of an object from an array. This
     * is used when the config is cached by Laravel.
     */
    public static function __set_state(array $stateData): static
    {
        return new static($stateData['handle'], $stateData);
    }

    protected function loginUrl(bool $cp = false): string
    {
        $parameters = ['provider' => $this->handle];

        if ($cp) {
            $parameters['cp'] = 1;
        }

        if ($returnUrl = request()->query('returnUrl')) {
            $parameters['returnUrl'] = $returnUrl;
        }

        return route('craft.auth.socialite.redirect', $parameters, false);
    }
}
