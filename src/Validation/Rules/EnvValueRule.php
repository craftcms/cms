<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Support\Arr;
use CraftCms\Cms\Support\Env;
use CraftCms\Cms\Support\Facades\Security;
use CraftCms\Cms\Support\Str;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\Rule as LegacyRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ConditionalRules;
use Override;
use Stringable;

/**
 * EnvValueRule validates attributes that can be set to either literal values,
 * environment variables (`$VARIABLE_NAME`), or aliases (`@aliasName`).
 *
 * The wrapped validation rules receive the parsed value, but the original raw
 * value is left untouched, so env vars and aliases can still be saved to project
 * config or model attributes.
 *
 * ---
 *
 * ```php
 * public function rules(): array
 * {
 *     return [
 *         'fromEmail' => new EnvValueRule(['required', 'string', 'email']),
 *         'mailer' => new EnvValueRule(['nullable', 'string', Rule::in($validMailers)]),
 *     ];
 * }
 * ```
 */
class EnvValueRule implements DataAwareRule, ValidationRule
{
    public bool $implicit = true;

    /**
     * @var array<int, Closure|LegacyRule|Stringable|ValidationRule|string>
     */
    private readonly array $rules;

    /**
     * @var array<string, mixed>
     */
    private array $data = [];

    /**
     * @param  Closure|LegacyRule|Stringable|ValidationRule|ConditionalRules|string|array<int, Closure|LegacyRule|Stringable|ValidationRule|ConditionalRules|string>  $rules
     */
    public function __construct(
        Closure|LegacyRule|Stringable|ValidationRule|ConditionalRules|string|array $rules,
        private readonly bool $showParsedValueInErrors = false,
    ) {
        $this->rules = Arr::wrap($rules);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    #[Override]
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parsedValue = is_string($value)
            ? ($this->isBoolean() ? Env::parseBoolean($value) : Env::parse($value))
            : $value;
        $data = $this->data;

        data_set($data, $attribute, $parsedValue);

        $validator = Validator::make($data, [
            $attribute => $this->rules,
        ]);

        if ($validator->passes()) {
            return;
        }

        foreach ($validator->errors()->get($attribute) as $message) {
            $fail($this->errorMessage($message, $value, $parsedValue));
        }
    }

    private function isBoolean(): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule === 'boolean' || $rule === 'bool') {
                return true;
            }
        }

        return false;
    }

    private function errorMessage(string $message, mixed $rawValue, mixed $parsedValue): string
    {
        if (
            ! $this->showParsedValueInErrors ||
            ! is_string($rawValue) ||
            Security::isSensitive($rawValue) ||
            ! is_scalar($parsedValue)
        ) {
            return $message;
        }

        return Str::finish($message, sprintf(' (%s)', $parsedValue));
    }
}
