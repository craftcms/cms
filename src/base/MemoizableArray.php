<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use craft\helpers\ArrayHelper;
use craft\helpers\Json;

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
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 3.5.8
 */
class MemoizableArray extends \ArrayObject
{
    private $_memoized = [];

    /**
     * Returns all items.
     *
     * @return array
     */
    public function all(): array
    {
        // It's not clear from the PHP docs whether there is a difference between
        // casting this as an array or calling getArrayCopy(). Casting feels safer though.
        return (array)$this;
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
    public function where(string $key, $value = true, bool $strict = false): self
    {
        $memKey = $this->_memKey(__METHOD__, $key, $value, $strict);

        if (!isset($this->_memoized[$memKey])) {
            $this->_memoized[$memKey] = new MemoizableArray(ArrayHelper::where($this, $key, $value, $strict, false));
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
     * @param mixed[] $values the value that `$key` should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against `$values`
     * @return self the filtered array
     */
    public function whereIn(string $key, array $values, bool $strict = false): self
    {
        $memKey = $this->_memKey(__METHOD__, $key, $values, $strict);

        if (!isset($this->_memoized[$memKey])) {
            $this->_memoized[$memKey] = new MemoizableArray(ArrayHelper::whereIn($this, $key, $values, $strict, false));
        }

        return $this->_memoized[$memKey];
    }

    /**
     * Returns the first value where a given key (the name of a sub-array key or sub-object property) is set to a given value.
     *
     * @param string $key the column name whose result will be used to index the array
     * @param mixed $value the value that `$key` should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against `$value`
     * @return mixed the first matching value, or `null` if no match is found
     */
    public function firstWhere(string $key, $value = true, bool $strict = false)
    {
        $memKey = $this->_memKey(__METHOD__, $key, $value, $strict);

        // Use array_key_exists() because it could be null
        if (!array_key_exists($memKey, $this->_memoized)) {
            $this->_memoized[$memKey] = ArrayHelper::firstWhere($this, $key, $value, $strict);
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
    private function _memKey(string $method, string $key, $value, bool $strict): string
    {
        if (!is_scalar($value)) {
            $value = Json::encode($value);
        }
        return "$method:$key:$value:$strict";
    }

    /**
     * @inheritdoc
     */
    public function append($value)
    {
        parent::append($value);
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function asort(int $sort_flags = SORT_REGULAR)
    {
        parent::asort($sort_flags);
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function exchangeArray($input)
    {
        parent::exchangeArray($input);
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function ksort(int $sort_flags = SORT_REGULAR)
    {
        parent::ksort($sort_flags);
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function natcasesort()
    {
        parent::natcasesort();
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function natsort()
    {
        parent::natsort();
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function offsetSet($index, $newval)
    {
        parent::offsetSet($index, $newval);
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function offsetUnset($index)
    {
        parent::offsetUnset($index);
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function uasort($cmp_function)
    {
        parent::uasort($cmp_function);
        $this->_memoized = [];
    }

    /**
     * @inheritdoc
     */
    public function uksort($cmp_function)
    {
        parent::uksort($cmp_function);
        $this->_memoized = [];
    }
}
