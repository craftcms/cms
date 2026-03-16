<?php

declare(strict_types=1);

namespace CraftCms\Cms\Support;

use ArrayIterator;
use Countable;
use Illuminate\Support\Collection;
use IteratorAggregate;

use function CraftCms\Cms\enum_value;

/**
 * MemoizableArray represents an array of values that need to be run through
 * `where()` or `firstWhere()` repeatedly, where it could be beneficial if
 * the results were memoized.
 *
 * Any class properties that are set to an instance of this class should be
 * excluded from class serialization:
 *
 * ```php
 * public function __serialize()
 * {
 *     $vars = get_object_vars($this);
 *     unset($vars['myMemoizedPropertyName']);
 *     return $vars;
 * }
 * ```
 *
 * @template T
 */
class MemoizableArray implements Countable, IteratorAggregate
{
    /** @var array<int|string, mixed> Normalized elements */
    private array $normalized = [];

    /** @var array<string, mixed> Memoized array elements */
    private array $memoized = [];

    /**
     * @param  array<T>  $elements  The items to be memoized
     * @param  callable|null  $normalizer  A method that the items should be normalized with when first
     *                                     returned by `all()` or `firstWhere()`.
     */
    public function __construct(
        private readonly array $elements,
        private readonly ?callable $normalizer = null,
    ) {}

    /**
     * @return array<T>
     */
    public function all(): array
    {
        return $this->normalize($this->elements);
    }

    /**
     * @return Collection<T>
     */
    public function collect(): Collection
    {
        return collect($this->all());
    }

    /**
     * Filters the array to only the values where a given key (the name of a
     * sub-array key or sub-object property) is set to a given value.
     *
     * Array keys are preserved by default.
     *
     * @param  string  $key  the column name whose result will be used to index the array
     * @param  mixed  $value  the value that `$key` should be compared with
     * @param  bool  $strict  whether a strict type comparison should be used when checking array element values against `$value`
     * @return self the filtered array
     */
    public function where(string $key, mixed $value = true, bool $strict = false): self
    {
        $memKey = $this->memKey('where', $key, $value, $strict);

        if (isset($this->memoized[$memKey])) {
            return $this->memoized[$memKey];
        }

        $filtered = [];
        foreach ($this->elements as $k => $element) {
            $elementValue = enum_value($this->getElementValue($element, $key));
            $compareValue = enum_value($value);
            if ($strict ? $elementValue === $compareValue : $elementValue == $compareValue) {
                $filtered[$k] = $element;
            }
        }

        return $this->memoized[$memKey] = new self(
            $filtered,
            isset($this->normalizer) ? fn ($element, $key) => $this->normalizeByKey($key) : null,
        );
    }

    /**
     * Filters the array to only the values where a given key (the name of a
     * sub-array key or sub-object property) is set to one of a given range
     * of values.
     *
     * Array keys are preserved by default.
     *
     * @param  string  $key  the column name whose result will be used to index the array
     * @param  array  $values  the value that `$key` should be compared with
     * @param  bool  $strict  whether a strict type comparison should be used when checking array element values against `$values`
     * @return self the filtered array
     */
    public function whereIn(string $key, array $values, bool $strict = false): self
    {
        $memKey = $this->memKey('whereIn', $key, $values, $strict);

        if (isset($this->memoized[$memKey])) {
            return $this->memoized[$memKey];
        }

        $compareValues = array_map(enum_value(...), $values);
        $filtered = [];
        foreach ($this->elements as $k => $element) {
            $elementValue = enum_value($this->getElementValue($element, $key));
            if (in_array($elementValue, $compareValues, $strict)) {
                $filtered[$k] = $element;
            }
        }

        return $this->memoized[$memKey] = new self(
            $filtered,
            isset($this->normalizer) ? fn ($element, $key) => $this->normalizeByKey($key) : null,
        );
    }

    /**
     * Returns the first value where a given key (the name of a sub-array key
     * or sub-object property) is set to a given value.
     *
     * @param  string  $key  the column name whose result will be used to index the array
     * @param  mixed  $value  the value that `$key` should be compared with
     * @param  bool  $strict  whether a strict type comparison should be used when checking array element values against `$value`
     * @return T|null the first matching value, or `null` if no match is found
     */
    public function firstWhere(string $key, mixed $value = true, bool $strict = false): mixed
    {
        $memKey = $this->memKey('firstWhere', $key, $value, $strict);

        // Use array_key_exists() because the value could be null
        if (array_key_exists($memKey, $this->memoized)) {
            return $this->memoized[$memKey];
        }

        $valueKey = null;
        foreach ($this->elements as $k => $element) {
            $elementValue = enum_value($this->getElementValue($element, $key));
            $compareValue = enum_value($value);
            if ($strict ? $elementValue === $compareValue : $elementValue == $compareValue) {
                $valueKey = $k;
                break;
            }
        }

        return $this->memoized[$memKey] = $this->normalizeByKey($valueKey);
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->normalize($this->elements));
    }

    public function count(): int
    {
        return count($this->elements);
    }

    private function normalize(array $elements): array
    {
        if (! isset($this->normalizer)) {
            return $elements;
        }

        return array_values(array_map($this->normalizeByKey(...), array_keys($elements)));
    }

    private function normalizeByKey(int|string|null $key): mixed
    {
        if ($key === null) {
            return null;
        }

        if (! isset($this->normalizer)) {
            return $this->elements[$key];
        }

        if (! isset($this->normalized[$key])) {
            $this->normalized[$key] = call_user_func($this->normalizer, $this->elements[$key], $key);
        }

        return $this->normalized[$key];
    }

    private function memKey(string $method, string $key, mixed $value, bool $strict): string
    {
        if (! is_scalar($value)) {
            $value = Json::encode($value);
        }

        return "{$method}:{$key}:{$value}:{$strict}";
    }

    private function getElementValue(mixed $element, string $key): mixed
    {
        if (is_array($element)) {
            return $element[$key] ?? null;
        }

        return $element->$key ?? null;
    }
}
