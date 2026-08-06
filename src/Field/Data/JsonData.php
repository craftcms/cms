<?php

declare(strict_types=1);

namespace CraftCms\Cms\Field\Data;

use ArrayAccess;
use ArrayIterator;
use BadMethodCallException;
use CraftCms\Cms\Component\Component;
use CraftCms\Cms\Component\Exceptions\InvalidCallException;
use CraftCms\Cms\Shared\Contracts\Serializable;
use CraftCms\Cms\Support\Json;
use CraftCms\Cms\Twig\AllowableInSandbox;
use CraftCms\Cms\Twig\Attributes\AllowedInSandbox;
use IteratorAggregate;
use Override;
use Stringable;
use Traversable;

/** @implements IteratorAggregate<mixed, mixed> */
class JsonData extends Component implements AllowableInSandbox, IteratorAggregate, Serializable, Stringable
{
    /** @param array<string, mixed>|object $config */
    public function __construct(
        private mixed $value,
        public array|object $config = [],
    ) {
        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->getJson();
    }

    /** @param list<mixed> $parameters */
    #[Override]
    public function __call($method, $parameters)
    {
        try {
            return parent::__call($method, $parameters);
        } catch (BadMethodCallException $e) {
            if (! empty($parameters)) {
                throw $e;
            }

            // This is probably just Twig falling back to calling a properly like it's a method
            return null;
        }
    }

    public function methodAllowedInSandbox(string $method): bool
    {
        return false;
    }

    public function propertyAllowedInSandbox(string $property): bool
    {
        return true;
    }

    #[AllowedInSandbox]
    public function getType(): string
    {
        $type = gettype($this->value);

        return match ($type) {
            'double' => 'float',
            default => $type,
        };
    }

    #[AllowedInSandbox]
    public function getValue(): mixed
    {
        return $this->value;
    }

    #[AllowedInSandbox]
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

    #[Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->value[$offset];
    }

    #[Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->value[$offset] = $value;
    }

    #[Override]
    public function offsetExists(mixed $offset): bool
    {
        if (is_string($this->value)) {
            return isset($this->value[$offset]);
        }

        if (is_array($this->value)) {
            return array_key_exists((string) $offset, $this->value);
        }

        if ($this->value instanceof ArrayAccess) {
            return $this->value->offsetExists($offset);
        }

        return false;
    }

    #[Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->value[$offset]);
    }

    /** @return Traversable<mixed, mixed> */
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
