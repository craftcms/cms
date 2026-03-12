<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use craft\helpers\DateTimeHelper;
use CraftCms\Cms\Support\Facades\I18N;
use CraftCms\Cms\Translation\Locale;
use DateTime;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use RuntimeException;

use function CraftCms\Cms\t;

class TimeRule implements DataAwareRule, ValidationRule
{
    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    private array $data = [];

    public function __construct(
        /**
         * @var string|null The minimum time allowed. Should be in the format `HH:MM` or reference another attribute under validation.
         *
         * @see $tooEarly for the customized message used when the time is too early
         */
        public ?string $min = null,

        /**
         * @var string|null The maximum time allowed. Should be in the format `HH:MM` or reference another attribute under validation.
         *
         * @see $tooLate for the customized message used when the time is too late
         */
        public ?string $max = null,

        /**
         * @var string|null user-defined error message used when the value is earlier than [[min]]
         */
        public ?string $tooEarly = null,

        /**
         * @var string|null user-defined error message used when the value is later than [[max]]
         */
        public ?string $tooLate = null,

        /**
         * @var string|null user-defined error message
         */
        public ?string $message = null,
    ) {
        $this->message ??= t('{attribute} must be a time.');
        $this->tooEarly ??= t('{attribute} must be no earlier than {min}.');
        $this->tooLate ??= t('{attribute} must be no later than {max}.');
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof DateTime) {
            $value = DateTimeHelper::toDateTime(['time' => $value], true);
        }

        if (! $value) {
            $fail($this->message);

            return;
        }

        if (isset($this->min)) {
            $min = $this->data[$this->min] ?? $this->min;
            $min = DateTimeHelper::toDateTime(['time' => $min], true);

            if (! $min) {
                throw new RuntimeException("Invalid minimum time: $min");
            }

            if ($value < $min) {
                $fail(t($this->tooEarly, [
                    'min' => I18N::getFormatter()->asTime($min, Locale::LENGTH_SHORT),
                ], locale: app()->getLocale()));
            }
        }

        if (isset($this->max)) {
            $max = $this->data[$this->max] ?? $this->max;
            $max = DateTimeHelper::toDateTime(['time' => $max], true);

            if (! $max) {
                throw new RuntimeException("Invalid maximum time: $max");
            }

            if ($value > $max) {
                $fail(t($this->tooLate, [
                    'max' => I18N::getFormatter()->asTime($max, Locale::LENGTH_SHORT),
                ], locale: app()->getLocale()));
            }
        }
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
