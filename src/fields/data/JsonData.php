<?php
/**
 * @link https://craftcms.com/
 * @copyright Copyright (c) Pixel & Tonic, Inc.
 * @license https://craftcms.github.io/license/
 */

namespace craft\fields\data;

use ArrayAccess;
use ArrayIterator;
use craft\base\Serializable;
use craft\helpers\Json;
use IteratorAggregate;
use Traversable;
use yii\base\BaseObject;
use yii\base\InvalidCallException;

/**
 * JSON field data class.
 *
 * @author Pixel & Tonic, Inc. <support@pixelandtonic.com>
 * @since 5.7.0
 */
class JsonData extends BaseObject implements ArrayAccess, IteratorAggregate, Serializable
{
    public function __construct(
        private mixed $value,
        array $config = [],
    ) {
        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->getJson();
    }

    public function getType(): string
    {
        $type = gettype($this->value);
        return match ($type) {
            'double' => 'float',
            default => $type,
        };
    }

    public function getValue(): mixed
    {
        return $this->value;
    }

    public function getJson(bool $pretty = false, string $indent = '  '): string
    {
        if (isset($this->value['__ERROR__'], $this->value['__VALUE__'])) {
            return $this->value['__VALUE__'];
        }

        $options = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if ($pretty) {
            $options |= JSON_PRETTY_PRINT;
        }

        $json = Json::encode($this->value, $options);

        if ($pretty) {
            return Json::reindent($json, $indent);
        }

        return $json;
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->value[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->value[$offset] = $value;
    }

    public function offsetExists(mixed $offset): bool
    {
        if (is_string($this->value)) {
            return isset($this->value[$offset]);
        }

        if (is_array($this->value)) {
            return array_key_exists($offset, $this->value);
        }

        if ($this->value instanceof ArrayAccess) {
            return $this->value->offsetExists($offset);
        }

        return false;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->value[$offset]);
    }

    public function getIterator(): Traversable
    {
        if (is_string($this->value)) {
            return new ArrayIterator(str_split($this->value));
        }

        if (is_array($this->value)) {
            return new ArrayIterator($this->value);
        }

        if ($this->value instanceof Traversable) {
            return $this->value;
        }

        throw new InvalidCallException(sprintf(
            'Cannot iterate over non-iterable type: %s',
            get_debug_type($this->value),
        ));
    }

    public function serialize(): mixed
    {
        return $this->value;
    }
}
