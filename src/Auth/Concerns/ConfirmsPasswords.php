<?php

declare(strict_types=1);

namespace CraftCms\Cms\Auth\Concerns;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

use function CraftCms\Cms\t;

trait ConfirmsPasswords
{
    protected function confirmPassword(): void
    {
        Session::passwordConfirmed();
    }

    protected function requireConfirmedPassword(?string $message = null): void
    {
        abort_unless(
            $this->isPasswordConfirmed(),
            423,
            $message ?? t('This action may only be performed with an elevated session.')
        );
    }

    protected function isPasswordConfirmed(): bool
    {
        $maximumSecondsSinceConfirmation = $this->passwordConfirmationDuration();

        if ($maximumSecondsSinceConfirmation === -1) {
            return true;
        }

        return $this->confirmedPasswordTimeout() !== 0;
    }

    protected function confirmedPasswordTimeout(): int|false
    {
        if (Auth::check()) {
            $maximumSecondsSinceConfirmation = $this->passwordConfirmationDuration();
            $confirmedAt = Session::get('auth.password_confirmed_at');

            if ($confirmedAt !== null) {
                $diff = now()->unix() - $confirmedAt;
                $remainingTime = $maximumSecondsSinceConfirmation - $diff;

                if ($remainingTime >= 0) {
                    return $remainingTime;
                }
            }
        }

        // If it has been disabled, return false.
        if ($this->passwordConfirmationDuration() === -1) {
            return false;
        }

        return 0;
    }

    private function passwordConfirmationDuration(): int
    {
        return (int) config('auth.password_timeout', 300);
    }
}
