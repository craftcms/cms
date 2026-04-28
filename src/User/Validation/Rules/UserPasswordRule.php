<?php

declare(strict_types=1);

namespace CraftCms\Cms\User\Validation\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Hash;

use function CraftCms\Cms\t;

readonly class UserPasswordRule implements ValidationRule
{
    public const int MIN_PASSWORD_LENGTH = 6;

    public const int MAX_PASSWORD_LENGTH = 160;

    public function __construct(
        private bool $forceDifferent = false,
        private ?string $currentPassword = null,
        private int $min = self::MIN_PASSWORD_LENGTH,
        private int $max = self::MAX_PASSWORD_LENGTH,
        private ?string $tooShort = null,
        private ?string $tooLong = null,
        private ?string $sameAsCurrent = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if ($value === '') {
            return;
        }

        $length = mb_strlen($value);

        if ($length < $this->min) {
            $fail($this->tooShort ?? t('{attribute} should contain at least {min, number} {min, plural, =1{character} other{characters}}.', [
                'min' => $this->min,
            ]));

            return;
        }

        if ($length > $this->max) {
            $fail($this->tooLong ?? t('{attribute} should contain at most {max, number} {max, plural, =1{character} other{characters}}.', [
                'max' => $this->max,
            ]));

            return;
        }

        if ($this->forceDifferent
            && $this->currentPassword !== null
            && Hash::check($value, $this->currentPassword)
        ) {
            $fail($this->sameAsCurrent ?? t('{attribute} must be set to a new password.'));
        }
    }
}
