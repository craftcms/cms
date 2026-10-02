<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Contracts;

use CraftCms\Cms\User\Elements\User as UserElement;
use Illuminate\Contracts\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Contracts\Auth\MustVerifyEmail;

interface CraftUser extends Authenticatable, Authorizable, CanResetPassword, MustVerifyEmail
{
    public function getCraftUserId(): ?int;

    public function asElement(): UserElement;

    public function isAdmin(): bool;

    public function getPreference(string $key, mixed $default = null): mixed;
}
