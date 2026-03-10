<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\OAuth;

use Laravel\Socialite\Contracts\User as SocialiteUser;
use RuntimeException;

final readonly class ProviderProfile
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function __construct(
        public string $handle,
        public string|int $id,
        public ?string $email,
        public ?string $name,
        public ?string $nickname,
        public ?string $avatar,
        public array $attributes,
    ) {}

    public static function fromSocialite(string $handle, SocialiteUser $user): self
    {
        return new self(
            handle: $handle,
            id: $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            nickname: $user->getNickname(),
            avatar: $user->getAvatar(),
            attributes: self::rawAttributes($user),
        );
    }

    public function defaultIdentifier(): string|int
    {
        if ($this->id !== '') {
            return $this->id;
        }

        if ($this->email !== null && $this->email !== '') {
            return $this->email;
        }

        throw new RuntimeException('Socialite user did not provide an ID or email address.');
    }

    /**
     * @return array<string, mixed>
     */
    private static function rawAttributes(SocialiteUser $user): array
    {
        if (method_exists($user, 'getRaw')) {
            $raw = $user->getRaw();

            return is_array($raw) ? $raw : [];
        }

        if (property_exists($user, 'user') && is_array($user->user)) {
            return $user->user;
        }

        return [];
    }
}
