<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\base;

use ArrayAccess;
use ArrayObject;
use Countable;
use craft\helpers\ArrayHelper;
use craft\helpers\Json;
use IteratorAggregate;
use ReturnTypeWillChange;

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
class MemoizableArray implements IteratorAggregate, ArrayAccess, Serializable, Countable
{
    /**
     * @var ArrayObject
     */
    private $_array;

    /**
     * @var array
     */
    private $_memoized = [];

    /**
     * @param array|object $array
     * @param int $flags
     * @param string $iteratorClass
     */
    public function __construct($array = [], int $flags = 0, string $iteratorClass = "ArrayIterator")
    {
        $this->_array = new ArrayObject($array, $flags, $iteratorClass);
    }

    /**
     * Returns all items.
     *
     * @return array<T>
     */
    public function all(): array
    {
        // It's not clear from the PHP docs whether there is a difference between
        // casting this as an array or calling getArrayCopy(). Casting feels safer though.
        return (array)$this->_array;
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
            $this->_memoized[$memKey] = new MemoizableArray(ArrayHelper::where($this->_array, $key, $value, $strict, false));
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
            $this->_memoized[$memKey] = new MemoizableArray(ArrayHelper::whereIn($this->_array, $key, $values, $strict, false));
        }

        return $this->_memoized[$memKey];
    }

    /**
     * Returns the first value where a given key (the name of a sub-array key or sub-object property) is set to a given value.
     *
     * @param string $key the column name whose result will be used to index the array
     * @param mixed $value the value that `$key` should be compared with
     * @param bool $strict whether a strict type comparison should be used when checking array element values against `$value`
     * @return T the first matching value, or `null` if no match is found
     */
    public function firstWhere(string $key, $value = true, bool $strict = false)
    {
        $memKey = $this->_memKey(__METHOD__, $key, $value, $strict);

        // Use array_key_exists() because it could be null
        if (!array_key_exists($memKey, $this->_memoized)) {
            $this->_memoized[$memKey] = ArrayHelper::firstWhere($this->_array, $key, $value, $strict);
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
     * Resets the memoized data.
     */
    private function _reset(): void
    {
        $this->_memoized = [];
    }

    /**
     * Appends the value
     *
     * @see https://www.php.net/manual/en/arrayobject.append.php
     */
    public function append($value)
    {
        $this->_array->append($value);
        $this->_reset();
    }

    /**
     * Sort the entries by value
     *
     * @see https://www.php.net/manual/en/arrayobject.asort.php
     */
    public function asort(int $sort_flags = SORT_REGULAR)
    {
        $this->_array->asort($sort_flags);
        $this->_reset();
    }

    /**
     * Get the number of public properties in the ArrayObject
     *
     * @see https://www.php.net/manual/en/arrayobject.count.php
     */
    #[ReturnTypeWillChange]
    public function count()
    {
        return $this->_array->count();
    }

    /**
     * Exchange the array for another one.
     *
     * @see https://www.php.net/manual/en/arrayobject.exchangearray.php
     */
    public function exchangeArray($input)
    {
        $this->_array->exchangeArray($input);
        $this->_reset();
    }

    /**
     * Creates a copy of the ArrayObject.
     *
     * @see https://www.php.net/manual/en/arrayobject.getarraycopy.php
     */
    public function getArrayCopy()
    {
        return $this->_array->getArrayCopy();
    }

    /**
     * Gets the behavior flags.
     *
     * @see https://www.php.net/manual/en/arrayobject.getflags.php
     */
    public function getFlags()
    {
        return $this->_array->getFlags();
    }

    /**
     * Create a new iterator from an ArrayObject instance
     *
     * @see https://www.php.net/manual/en/arrayobject.getiterator.php
     */
    #[ReturnTypeWillChange]
    public function getIterator()
    {
        return $this->_array->getIterator();
    }

    /**
     * Gets the iterator classname for the ArrayObject.
     *
     * @see https://www.php.net/manual/en/arrayobject.getiteratorclass.php
     */
    public function getIteratorClass()
    {
        return $this->_array->getIteratorClass();
    }

    /**
     * Sort the entries by key
     *
     * @see https://www.php.net/manual/en/arrayobject.ksort.php
     */
    public function ksort(int $sort_flags = SORT_REGULAR)
    {
        $this->_array->ksort($sort_flags);
        $this->_reset();
    }

    /**
     * Sort an array using a case insensitive "natural order" algorithm
     *
     * @see https://www.php.net/manual/en/arrayobject.natcasesort.php
     */
    public function natcasesort()
    {
        $this->_array->natcasesort();
        $this->_reset();
    }

    /**
     * Sort entries using a "natural order" algorithm
     *
     * @see https://www.php.net/manual/en/arrayobject.natsort.php
     */
    public function natsort()
    {
        $this->_array->natsort();
        $this->_reset();
    }

    /**
     * Returns whether the requested index exists
     *
     * @see https://www.php.net/manual/en/arrayobject.offsetexists.php
     */
    #[ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return $this->_array->offsetExists($offset);
    }

    /**
     * Returns the value at the specified index
     *
     * @see https://www.php.net/manual/en/arrayobject.offsetget.php
     */
    #[ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->_array->offsetGet($offset);
    }

    /**
     * Sets the value at the specified index to newval
     *
     * @see https://www.php.net/manual/en/arrayobject.offsetset.php
     */
    #[ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->_array->offsetSet($offset, $value);
        $this->_reset();
    }

    /**
     * Unsets the value at the specified index
     *
     * @see https://www.php.net/manual/en/arrayobject.offsetunset.php
     */
    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        $this->_array->offsetUnset($offset);
        $this->_reset();
    }

    /**
     * Serialize an ArrayObject
     *
     * @see https://www.php.net/manual/en/arrayobject.serialize.php
     */
    public function serialize()
    {
        return $this->_array->serialize();
    }

    /**
     * Sets the behavior flags.
     *
     * @see https://www.php.net/manual/en/arrayobject.setflags.php
     */
    public function setFlags($flags)
    {
        $this->_array->setFlags($flags);
    }

    /**
     * Sets the iterator classname for the ArrayObject.
     *
     * @see https://www.php.net/manual/en/arrayobject.setiteratorclass.php
     */
    public function setIteratorClass($iteratorClass)
    {
        $this->_array->setIteratorClass($iteratorClass);
    }

    /**
     * Sort the entries with a user-defined comparison function and maintain key association
     *
     * @see https://www.php.net/manual/en/arrayobject.uasort.php
     */
    public function uasort($cmp_function)
    {
        $this->_array->uasort($cmp_function);
        $this->_reset();
    }

    /**
     * Sort the entries by keys using a user-defined comparison function
     *
     * @see https://www.php.net/manual/en/arrayobject.uksort.php
     */
    public function uksort($cmp_function)
    {
        $this->_array->uksort($cmp_function);
        $this->_reset();
    }

    /**
     * Unserialize an ArrayObject
     *
     * @see https://www.php.net/manual/en/arrayobject.unserialize.php
     */
    public function unserialize($data)
    {
        $this->_array->unserialize($data);
        $this->_reset();
    }
}
