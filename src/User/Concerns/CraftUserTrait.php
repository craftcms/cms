<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Concerns;

use CraftCms\Cms\Support\Facades\Users;
use CraftCms\Cms\User\Contracts\CraftUser;
use CraftCms\Cms\User\Elements\User as UserElement;
use CraftCms\Cms\User\Notifications\ActivationNotification;
use CraftCms\Cms\User\Notifications\ResetPasswordNotification;
use CraftCms\Cms\User\Notifications\VerifyEmailNotification;
use Throwable;

/**
 * @mixin UserElement
 * @mixin CraftUser
 */
trait CraftUserTrait
{
    abstract public function asElement(): UserElement;

    public function getCraftUserId(): ?int
    {
        return $this->id ?? null;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->admin;
    }

    protected function defaultName(): string
    {
        return $this->fullName ?? (string) $this->username;
    }

    protected function defaultFriendlyName(): ?string
    {
        return $this->firstName ?? $this->username;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendActivationNotification(string $token): void
    {
        $this->notify(new ActivationNotification($token));
    }

    public function hasVerifiedEmail(): bool
    {
        return is_null($this->unverifiedEmail);
    }

    public function markEmailAsVerified(): bool
    {
        try {
            Users::verifyEmailForUser($this->asElement());

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function markEmailAsUnverified(): bool
    {
        try {
            Users::unverifyEmailForUser($this->asElement());

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification(Users::setVerificationCodeOnUser($this->asElement())));
    }

    public function getEmailForVerification(): string
    {
        return $this->unverifiedEmail ?? $this->email;
    }

    public function getPreference(string $key, mixed $default = null): mixed
    {
        return $this->id ? Users::getUserPreference($this->id, $key, $default) : $default;
    }
}
