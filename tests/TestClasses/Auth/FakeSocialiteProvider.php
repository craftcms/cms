<?php

declare(strict_types=1);

namespace CraftCms\Cms\Tests\TestClasses\Auth;

use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

final class FakeSocialiteProvider implements Provider
{
    /** @var array<string, array<string, mixed>> */
    public static array $configs = [];

    /** @var array<string, array<int, string>> */
    public static array $scopes = [];

    /** @var array<string, bool> */
    public static array $stateless = [];

    /** @var array<string, array<string, mixed>> */
    public static array $with = [];

    /** @var array<string, SocialiteUser> */
    public static array $users = [];

    public function __construct(
        private readonly string $driver,
    ) {}

    public static function reset(): void
    {
        self::$configs = [];
        self::$scopes = [];
        self::$stateless = [];
        self::$with = [];
        self::$users = [];
    }

    public static function fake(string $driver, SocialiteUser $user): void
    {
        self::$users[$driver] = $user;
    }

    public function redirect(): RedirectResponse
    {
        $this->captureConfig();

        return redirect("/oauth/{$this->driver}");
    }

    public function user(): SocialiteUser
    {
        $this->captureConfig();

        if (! isset(self::$users[$this->driver])) {
            throw new RuntimeException("No fake Socialite user registered for [{$this->driver}].");
        }

        return self::$users[$this->driver];
    }

    /**
     * @param  array<int, string>|string  $scopes
     */
    public function scopes(array|string $scopes): self
    {
        self::$scopes[$this->driver] = array_values((array) $scopes);

        return $this;
    }

    public function stateless(): self
    {
        self::$stateless[$this->driver] = true;

        return $this;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function with(array $parameters): self
    {
        self::$with[$this->driver] = $parameters;

        return $this;
    }

    private function captureConfig(): void
    {
        self::$configs[$this->driver] = (array) config("services.{$this->driver}");
    }
}
