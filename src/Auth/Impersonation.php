<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth;

use CraftCms\Cms\User\Elements\User;
use Illuminate\Container\Attributes\Singleton;
use Illuminate\Session\SessionManager;

#[Singleton]
final readonly class Impersonation
{
    private const string SESSION_KEY = '__impersonator_id';

    private ?User $impersonator;

    public function __construct(
        private SessionManager $session,
    ) {}

    public function getImpersonator(): ?User
    {
        if (isset($this->impersonator)) {
            return $this->impersonator;
        }

        $impersonatorId = $this->session->get(self::SESSION_KEY);

        if (! $impersonatorId) {
            return null;
        }

        $impersonator = User::find()->id($impersonatorId)->first();

        if ($impersonator?->can('impersonateUsers')) {
            return $this->impersonator = $impersonator;
        }

        return null;
    }

    public function getImpersonatorId(): ?int
    {
        return $this->getImpersonator()?->id;
    }

    public function setImpersonatorId(?int $id): void
    {
        if ($id) {
            $this->session->put(self::SESSION_KEY, $id);

            return;
        }

        $this->session->forget(self::SESSION_KEY);
        $this->impersonator = null;
    }

    public function isImpersonating(): bool
    {
        return $this->getImpersonatorId() !== null;
    }
}
