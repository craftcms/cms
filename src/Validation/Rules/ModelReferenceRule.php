<?php

declare(strict_types=1);

namespace CraftCms\Cms\Validation\Rules;

use Closure;
use CraftCms\Cms\Shared\Enums\ModelIdentifier;
use Illuminate\Contracts\Validation\ValidationRule;
use Override;

/**
 * Validates that a value references an existing model by one of the allowed identifiers.
 *
 * Empty values pass; combine with `required` to require a value.
 *
 * @since 6.0.0
 */
abstract readonly class ModelReferenceRule implements ValidationRule
{
    /**
     * @param  ModelIdentifier[]|null  $by
     * @param  Closure(string): string|string|null  $message  The message for a value that doesn’t reference a model; a closure receives the value.
     */
    public function __construct(
        protected ?array $by = null,
        protected Closure|string|null $message = null,
    ) {}

    /**
     * Returns the model the value references, or null if there isn’t one.
     */
    abstract protected function resolve(mixed $value): ?object;

    /**
     * Returns the error message for a value that doesn’t reference a model.
     */
    abstract protected function defaultMessage(string $value): string;

    #[Override]
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (blank($value) || $this->resolve($value) !== null) {
            return;
        }

        $value = is_scalar($value) ? (string) $value : get_debug_type($value);

        $fail(match (true) {
            $this->message instanceof Closure => ($this->message)($value),
            $this->message !== null => $this->message,
            default => $this->defaultMessage($value),
        });
    }
}
