<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Events;

use CraftCms\Cms\User\Elements\User;
use SensitiveParameter;

final class SettingPassword
{
    public function __construct(
        public User $user,
        #[SensitiveParameter]
        public string $code,
        #[SensitiveParameter]
        public string $newPassword,
        public string $status,
    ) {}
}
