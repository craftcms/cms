<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use ArrayIterator;
use Countable;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use IteratorAggregate;

/**
 * MemoizableArray represents an array of values that need to be run through [[ArrayHelper::where()]] or [[ArrayHelper::firstWhere()]] repeatedly,
 * where it could be beneficial if the results were memoized.
 *
 * Any class properties that are set to an instance of this class should be excluded from class serialization:
 *
 * ```php
 * public function __serialize()
 * {
 *     $vars = get_object_vars($this);
 *     unset($vars['myMemoizedPropertyName'];
 *     return $vars;
 * }
 * ```
 *
 * @template T
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.8
 */
class MemoizableArray implements IteratorAggregate, Countable
{
    /**
     * @var array Array elements
     */
    private array $_elements;

    /**
     * @var callable|null Normalizer method
     */
    private $_normalizer;

    /**
     * @var array Normalized elements
     */
    private array $_normalized = [];

    /**
     * @var array Memoized array elements
     */
    private array $_memoized = [];

    /**
     * Constructor
     *
     * @param array $elements The items to be memoized
     * @param callable|null $normalizer A method that the items should be normalized with when first returned by
     * [[all()]] or [[firstWhere()]].
     */
    public function __construct(array $elements, ?callable $normalizer = null)
    {
        $this->_elements = $elements;
        $this->_normalizer = $normalizer;
    }

    private function normalize(array $elements): array
    {
        if (!isset($this->_normalizer)) {
            return $elements;
        }

        return array_values(array_map(fn($key) => $this->normalizeByKey($key), array_keys($elements)));
    }

    private function normalizeByKey(int|string|null $key): mixed
    {
        if ($key === null) {
            return null;
        }

        if (!isset($this->_normalizer)) {
            return $this->_elements[$key];
        }

        if (!isset($this->_normalized[$key])) {
            $this->_normalized[$key] = call_user_func($this->_normalizer, $this->_elements[$key], $key);
        }

        return $this->_normalized[$key];
    }

    /**
     * Returns all items.
     *
     * @return array
     * @phpstan-return array<T>
     */
    public function all(): array
    {
        return $this->normalize($this->_elements);
    }

    /**
     * Filters the array to only the values where a given key (the name of a sub-array key or sub-object property) is set to a given value.
     *
     * Array keys are preserved by default.
     *
     * @param string $key the column name whose result will be used to index the array
     * @param mixed $value the value that `$key` should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against `$value`
     * @return self the filtered array
     */
    public function where(string $key, mixed $value = true, bool $strict = false): self
    {
        $memKey = $this->_memKey(__METHOD__, $key, $value, $strict);

        if (!isset($this->_memoized[$memKey])) {
            $this->_memoized[$memKey] = new MemoizableArray(
                ArrayHelper::where($this->_elements, $key, $value, $strict),
                isset($this->_normalizer) ? fn($element, $key) => $this->normalizeByKey($key) : null,
            );
        }

        return $this->_memoized[$memKey];
    }

    /**
     * Filters the array to only the values where a given key (the name of a sub-array key or sub-object property)
     * is set to one of a given range of values.
     *
     * Array keys are preserved by default.
     *
     * @param string $key the column name whose result will be used to index the array
     * @param array $values the value that `$key` should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against `$values`
     * @return self the filtered array
     */
    public function whereIn(string $key, array $values, bool $strict = false): self
    {
        $memKey = $this->_memKey(__METHOD__, $key, $values, $strict);

        if (!isset($this->_memoized[$memKey])) {
            $this->_memoized[$memKey] = new MemoizableArray(
                ArrayHelper::whereIn($this->_elements, $key, $values, $strict),
                isset($this->_normalizer) ? fn($element, $key) => $this->normalizeByKey($key) : null,
            );
        }

        return $this->_memoized[$memKey];
    }

    /**
     * Returns the first value where a given key (the name of a sub-array key or sub-object property) is set to a given value.
     *
     * @param string $key the column name whose result will be used to index the array
     * @param mixed $value the value that `$key` should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against `$value`
     * @return T|null the first matching value, or `null` if no match is found
     */
    public function firstWhere(string $key, mixed $value = true, bool $strict = false)
    {
        $memKey = $this->_memKey(__METHOD__, $key, $value, $strict);

        // Use array_key_exists() because it could be null
        if (!array_key_exists($memKey, $this->_memoized)) {
            ArrayHelper::firstWhere($this->_elements, $key, $value, $strict, valueKey: $valueKey);
            $this->_memoized[$memKey] = $this->normalizeByKey($valueKey);
        }

        return $this->_memoized[$memKey];
    }

    /**
     * Generates a memoization key.
     *
     * @param string $method
     * @param string $key
     * @param mixed $value
     * @param bool $strict
     * @return string
     */
    private function _memKey(string $method, string $key, mixed $value, bool $strict): string
    {
        if (!is_scalar($value)) {
            $value = Json::encode($value);
        }
        return "$method:$key:$value:$strict";
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->normalize($this->_elements));
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->_elements);
    }
}
